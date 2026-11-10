<?php

declare(strict_types=1);

namespace PDV\Controllers;

use PDOException;
use PDV\Models\Customer;
use PDV\Repositories\CustomerRepository;
use Throwable;

final class CustomerController
{
    public function __construct(private CustomerRepository $customers)
    {
    }

    public function index(): array
    {
        $customers = $this->customers->findAll();
        $csrfToken = $this->csrfToken();
        $success = $_SESSION['customer_success'] ?? null;
        unset($_SESSION['customer_success']);

        return $this->page('customers/index', 'Clientes', '', [
            'customers' => $customers,
            'csrfToken' => $csrfToken,
            'success' => $success,
        ]);
    }

    public function create(): array
    {
        return $this->formPage('customers/create', 'Novo cliente');
    }

    public function store(): array
    {
        if (!$this->hasValidCsrfToken()) {
            return $this->forbidden();
        }

        $values = $this->submittedValues();
        $normalizedCpf = $this->normalizeCpf($values['cpf']);
        $errors = $this->validate($values, $normalizedCpf);

        if ($errors === [] && $this->customers->findByCpf($normalizedCpf) !== null) {
            $errors['cpf'] = 'Este CPF já está cadastrado.';
        }

        if ($errors !== []) {
            return $this->formPage('customers/create', 'Novo cliente', $values, $errors, 422);
        }

        try {
            $this->customers->create(new Customer(
                null,
                $values['nome'],
                $normalizedCpf,
                $values['telefone'] === '' ? null : $values['telefone'],
                $values['email'] === '' ? null : $values['email'],
            ));
        } catch (PDOException $exception) {
            if (!$this->isUniqueViolation($exception)) {
                throw $exception;
            }

            return $this->formPage('customers/create', 'Novo cliente', $values, [
                'cpf' => 'Este CPF já está cadastrado.',
            ], 422);
        }

        return $this->redirectAfterSuccess('Cliente cadastrado com sucesso.');
    }

    public function edit(): array
    {
        $id = $this->validatedId($_GET['id'] ?? null);
        $customer = $id === null ? null : $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound();
        }

        return $this->formPage(
            'customers/edit',
            'Editar cliente',
            $this->customerValues($customer),
            [],
            200,
            $customer->id,
        );
    }

    public function update(): array
    {
        if (!$this->hasValidCsrfToken()) {
            return $this->forbidden();
        }

        $id = $this->validatedId($_POST['id'] ?? null);
        $customer = $id === null ? null : $this->customers->findById($id);

        if ($customer === null) {
            return $this->notFound();
        }

        $values = $this->submittedValues();
        $normalizedCpf = $this->normalizeCpf($values['cpf']);
        $errors = $this->validate($values, $normalizedCpf);
        $existingCustomer = $errors === [] ? $this->customers->findByCpf($normalizedCpf) : null;

        if ($existingCustomer !== null && $existingCustomer->id !== $customer->id) {
            $errors['cpf'] = 'Este CPF já está cadastrado.';
        }

        if ($errors !== []) {
            return $this->formPage('customers/edit', 'Editar cliente', $values, $errors, 422, $customer->id);
        }

        try {
            $updated = $this->customers->update(new Customer(
                $customer->id,
                $values['nome'],
                $normalizedCpf,
                $values['telefone'] === '' ? null : $values['telefone'],
                $values['email'] === '' ? null : $values['email'],
                $customer->createdAt,
            ));
        } catch (PDOException $exception) {
            if (!$this->isUniqueViolation($exception)) {
                throw $exception;
            }

            return $this->formPage('customers/edit', 'Editar cliente', $values, [
                'cpf' => 'Este CPF já está cadastrado.',
            ], 422, $customer->id);
        }

        return $updated ? $this->redirectAfterSuccess('Cliente atualizado com sucesso.') : $this->notFound();
    }

    public function delete(): array
    {
        if (!$this->hasValidCsrfToken()) {
            return $this->forbidden();
        }

        $id = $this->validatedId($_POST['id'] ?? null);

        if ($id === null || !$this->customers->delete($id)) {
            return $this->notFound();
        }

        return $this->redirectAfterSuccess('Cliente excluído com sucesso.');
    }

    private function formPage(
        string $view,
        string $title,
        array $values = [],
        array $errors = [],
        int $status = 200,
        ?int $id = null,
    ): array {
        return $this->page($view, $title, '', [
            'csrfToken' => $this->csrfToken(),
            'values' => $values + ['nome' => '', 'cpf' => '', 'telefone' => '', 'email' => ''],
            'errors' => $errors,
            'id' => $id,
        ], $status);
    }

    private function page(string $view, string $title, string $message, array $data = [], int $status = 200): array
    {
        http_response_code($status);

        return [
            'view' => $view,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ];
    }

    private function notFound(): array
    {
        return $this->page(
            'errors/404',
            'Página não encontrada',
            'O cliente solicitado não foi encontrado.',
            [],
            404,
        );
    }

    private function forbidden(): array
    {
        return $this->page(
            'errors/403',
            'Requisição não autorizada',
            'Não foi possível validar esta requisição. Atualize a página e tente novamente.',
            [],
            403,
        );
    }

    private function redirectAfterSuccess(string $message): array
    {
        $this->startSession();
        $_SESSION['customer_success'] = $message;

        return ['redirect' => '/clientes'];
    }

    private function submittedValues(): array
    {
        $cpf = $_POST['cpf'] ?? '';

        return [
            'nome' => $this->postString('nome'),
            'cpf' => is_string($cpf) ? $cpf : '',
            'telefone' => $this->postString('telefone'),
            'email' => $this->postString('email'),
        ];
    }

    private function validate(array $values, string $normalizedCpf): array
    {
        $errors = [];

        if ($values['nome'] === '') {
            $errors['nome'] = 'Informe o nome do cliente.';
        } elseif (preg_match('/^.{1,255}$/us', $values['nome']) !== 1) {
            $errors['nome'] = 'O nome deve ter no máximo 255 caracteres.';
        }

        if (!$this->isValidCpf($normalizedCpf)) {
            $errors['cpf'] = 'Informe um CPF válido.';
        }

        if ($values['telefone'] !== '' && preg_match('/^.{1,30}$/us', $values['telefone']) !== 1) {
            $errors['telefone'] = 'O telefone deve ter no máximo 30 caracteres.';
        }

        if ($values['email'] !== '') {
            if (strlen($values['email']) > 254 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'] = 'Informe um e-mail válido.';
            }
        }

        return $errors;
    }

    private function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^[0-9]{11}$/', $cpf) !== 1 || preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($digitPosition = 9; $digitPosition <= 10; $digitPosition++) {
            $sum = 0;
            for ($position = 0; $position < $digitPosition; $position++) {
                $sum += (int) $cpf[$position] * (($digitPosition + 1) - $position);
            }

            $digit = ($sum * 10) % 11;
            $digit = $digit === 10 ? 0 : $digit;

            if ($digit !== (int) $cpf[$digitPosition]) {
                return false;
            }
        }

        return true;
    }

    private function normalizeCpf(string $cpf): string
    {
        if (preg_match('/^[0-9]{11}$/', $cpf) === 1) {
            return $cpf;
        }

        if (preg_match('/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/', $cpf) === 1) {
            return str_replace(['.', '-'], '', $cpf);
        }

        return '';
    }

    private function validatedId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    private function customerValues(Customer $customer): array
    {
        return [
            'nome' => $customer->nome,
            'cpf' => $customer->cpf,
            'telefone' => $customer->telefone ?? '',
            'email' => $customer->email ?? '',
        ];
    }

    private function csrfToken(): string
    {
        $this->startSession();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    private function hasValidCsrfToken(): bool
    {
        $token = $_POST['_csrf'] ?? null;

        return is_string($token) && hash_equals($this->csrfToken(), $token);
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }
    }

    private function isUniqueViolation(PDOException $exception): bool
    {
        return (string) $exception->getCode() === '23505'
            || ($exception->errorInfo[0] ?? null) === '23505';
    }
}