<?php

declare(strict_types=1);

namespace PDV\Controllers;

use DateTimeImmutable;
use DomainException;
use PDOException;
use PDV\Repositories\ProductRepository;
use PDV\Services\CsrfProtection;
use PDV\Services\ProductImageStorage;
use Throwable;

final class ProductController
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(
        private ProductRepository $products,
        private ProductImageStorage $images,
    ) {
    }

    public function index(): array
    {
        $search = $this->queryString('q');
        $products = $this->products->findAll($search);
        foreach ($products as &$product) {
            $quantity = (int) $product['quantidade'];
            $product['stockStatus'] = $quantity === 0
                ? 'Zerado'
                : ($quantity <= self::LOW_STOCK_THRESHOLD ? 'Baixo' : 'Disponível');
            $product['imageUrl'] = $this->images->publicUrl(
                is_string($product['imagem'] ?? null) ? $product['imagem'] : null,
                '',
            );
        }
        unset($product);

        CsrfProtection::startSession();
        $success = $_SESSION['product_success'] ?? null;
        $error = $_SESSION['product_error'] ?? null;
        unset($_SESSION['product_success'], $_SESSION['product_error']);

        return $this->page('products/index', 'Produtos', [
            'products' => $products,
            'search' => $search,
            'success' => $success,
            'error' => $error,
            'csrfToken' => CsrfProtection::token(),
        ]);
    }

    public function create(): array
    {
        return $this->formPage('products/create', 'Novo produto', $this->emptyValues(), []);
    }

    public function store(): array
    {
        if (!CsrfProtection::isValid($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        $values = $this->submittedValues();
        [$errors, $normalized] = $this->validate($values);
        $newImage = null;

        if ($errors === [] && $this->hasUploadedImage()) {
            try {
                $newImage = $this->images->store($_FILES['imagemProduto']);
            } catch (DomainException $exception) {
                $errors['imagem'] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            return $this->formPage('products/create', 'Novo produto', $values, $errors, 422);
        }

        $attributes = $this->attributes($normalized, $newImage ?? '');
        try {
            $this->products->create($attributes);
        } catch (Throwable $exception) {
            $this->images->deleteManaged($newImage);
            throw $exception;
        }

        return $this->redirectWithFlash('product_success', 'Produto cadastrado com sucesso.');
    }

    public function edit(): array
    {
        $id = $this->validatedId($_GET['id'] ?? null);
        $product = $id === null ? null : $this->products->findForManagementById($id);
        if ($product === null) {
            return $this->notFound();
        }

        $values = $this->valuesFromProduct($product);

        return $this->formPage('products/edit', 'Editar produto', $values, [], 200, $id, $product['imagem'] ?? null);
    }

    public function update(): array
    {
        if (!CsrfProtection::isValid($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        $id = $this->validatedId($_POST['id'] ?? null);
        $product = $id === null ? null : $this->products->findForManagementById($id);
        if ($product === null) {
            return $this->notFound();
        }

        $values = $this->submittedValues();
        [$errors, $normalized] = $this->validate($values);
        $newImage = null;

        if ($errors === [] && $this->hasUploadedImage()) {
            try {
                $newImage = $this->images->store($_FILES['imagemProduto']);
            } catch (DomainException $exception) {
                $errors['imagem'] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            return $this->formPage(
                'products/edit',
                'Editar produto',
                $values,
                $errors,
                422,
                $id,
                is_string($product['imagem'] ?? null) ? $product['imagem'] : null,
            );
        }

        $existingImage = is_string($product['imagem'] ?? null) ? $product['imagem'] : null;
        $image = $newImage ?? ($existingImage ?? '');
        try {
            $updated = $this->products->update($id, $this->attributes($normalized, $image));
        } catch (Throwable $exception) {
            $this->images->deleteManaged($newImage);
            throw $exception;
        }

        if (!$updated) {
            $this->images->deleteManaged($newImage);
            return $this->notFound();
        }

        if ($newImage !== null && $existingImage !== $newImage) {
            $this->images->deleteManaged($existingImage);
        }

        return $this->redirectWithFlash('product_success', 'Produto atualizado com sucesso.');
    }

    public function delete(): array
    {
        if (!CsrfProtection::isValid($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        $id = $this->validatedId($_POST['id'] ?? null);
        $product = $id === null ? null : $this->products->findForManagementById($id);
        if ($product === null) {
            return $this->notFound();
        }

        try {
            if (!$this->products->delete($id)) {
                return $this->notFound();
            }
        } catch (PDOException $exception) {
            if ($this->isForeignKeyViolation($exception)) {
                return $this->redirectWithFlash(
                    'product_error',
                    'Este produto está associado a vendas e não pode ser excluído.',
                );
            }

            throw $exception;
        }

        $this->images->deleteManaged(is_string($product['imagem'] ?? null) ? $product['imagem'] : null);

        return $this->redirectWithFlash('product_success', 'Produto excluído com sucesso.');
    }

    private function formPage(
        string $view,
        string $title,
        array $values,
        array $errors,
        int $status = 200,
        ?int $id = null,
        ?string $currentImage = null,
    ): array {
        return $this->page($view, $title, [
            'values' => $values + $this->emptyValues(),
            'errors' => $errors,
            'id' => $id,
            'currentImage' => $currentImage,
            'imageUrl' => $this->images->publicUrl($currentImage, ''),
            'csrfToken' => CsrfProtection::token(),
        ], $status);
    }

    private function emptyValues(): array
    {
        return [
            'cod' => '',
            'nome' => '',
            'categoria' => '',
            'validade' => '',
            'quantidade' => '0',
            'preco_unitario' => '0.00',
            'preco_pacote' => '0.00',
        ];
    }

    private function submittedValues(): array
    {
        $values = [];
        foreach (array_keys($this->emptyValues()) as $key) {
            $value = $_POST[$key] ?? '';
            $values[$key] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    private function validate(array $values): array
    {
        $errors = [];
        $normalized = $values;

        if ($values['cod'] === '') {
            $errors['cod'] = 'Informe o código do produto.';
        }
        if ($values['nome'] === '') {
            $errors['nome'] = 'Informe o nome do produto.';
        }

        if ($values['validade'] !== '' && !$this->isValidDate($values['validade'])) {
            $errors['validade'] = 'Informe uma data válida.';
        }
        $normalized['validade'] = $values['validade'] === '' ? null : $values['validade'];

        if (preg_match('/\A[0-9]+\z/', $values['quantidade']) !== 1) {
            $errors['quantidade'] = 'A quantidade deve ser um inteiro igual ou maior que zero.';
        } else {
            $quantity = filter_var($values['quantidade'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($quantity === false) {
                $errors['quantidade'] = 'A quantidade informada é inválida.';
            } else {
                $normalized['quantidade'] = $quantity;
            }
        }

        foreach (['preco_unitario', 'preco_pacote'] as $priceField) {
            $price = $this->normalizePrice($values[$priceField]);
            if ($price === null) {
                $errors[$priceField] = 'Informe um preço válido, igual ou maior que zero, com até duas casas decimais.';
            } else {
                $normalized[$priceField] = $price;
            }
        }

        return [$errors, $normalized];
    }

    private function normalizePrice(string $value): ?string
    {
        $value = str_replace(',', '.', trim($value));
        if (preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $value) !== 1) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return $whole . '.' . str_pad($fraction, 2, '0');
    }

    private function attributes(array $values, string $image): array
    {
        return [
            'cod' => $values['cod'],
            'nome' => $values['nome'],
            'categoria' => $values['categoria'],
            'validade' => $values['validade'],
            'quantidade' => $values['quantidade'],
            'preco_unitario' => $values['preco_unitario'],
            'preco_pacote' => $values['preco_pacote'],
            'imagem' => $image,
        ];
    }

    private function valuesFromProduct(array $product): array
    {
        return [
            'cod' => (string) ($product['cod'] ?? ''),
            'nome' => (string) ($product['nome'] ?? ''),
            'categoria' => (string) ($product['categoria'] ?? ''),
            'validade' => (string) ($product['validade'] ?? ''),
            'quantidade' => (string) ($product['quantidade'] ?? '0'),
            'preco_unitario' => (string) ($product['preco_unitario'] ?? '0.00'),
            'preco_pacote' => (string) ($product['preco_pacote'] ?? '0.00'),
        ];
    }

    private function hasUploadedImage(): bool
    {
        $file = $_FILES['imagemProduto'] ?? null;

        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date;
    }

    private function validatedId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function queryString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function redirectWithFlash(string $key, string $message): array
    {
        CsrfProtection::startSession();
        $_SESSION[$key] = $message;

        return ['redirect' => '/produtos'];
    }

    private function forbidden(): array
    {
        return $this->page('errors/403', 'Requisição não autorizada', [
            'message' => 'Não foi possível validar esta requisição. Atualize a página e tente novamente.',
        ], 403);
    }

    private function notFound(): array
    {
        return $this->page('errors/404', 'Produto não encontrado', [
            'message' => 'O produto solicitado não foi encontrado.',
        ], 404);
    }

    private function page(string $view, string $title, array $data = [], int $status = 200): array
    {
        http_response_code($status);

        return ['view' => $view, 'title' => $title, 'message' => $data['message'] ?? '', 'data' => $data];
    }

    private function isForeignKeyViolation(PDOException $exception): bool
    {
        return (string) $exception->getCode() === '23503'
            || ($exception->errorInfo[0] ?? null) === '23503';
    }
}