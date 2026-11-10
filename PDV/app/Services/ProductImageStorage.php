<?php

declare(strict_types=1);

namespace PDV\Services;

use DomainException;
use finfo;
use RuntimeException;

final class ProductImageStorage
{
    private const MAX_SIZE = 2_097_152;
    private const ALLOWED_EXTENSIONS = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private string $directory;

    public function __construct(string $publicDirectory)
    {
        $this->directory = rtrim($publicDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'products';
    }

    public function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException('Não foi possível receber a imagem enviada.');
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        $originalName = $file['name'] ?? null;
        $size = $file['size'] ?? null;

        if (!is_string($temporaryPath) || !is_string($originalName) || !is_int($size)) {
            throw new DomainException('O arquivo de imagem é inválido.');
        }
        if ($size < 1 || $size > self::MAX_SIZE) {
            throw new DomainException('A imagem deve ter até 2 MB.');
        }
        if (!is_uploaded_file($temporaryPath)) {
            throw new DomainException('O arquivo de imagem é inválido.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $allowedExtensions = self::ALLOWED_EXTENSIONS[$mimeType] ?? [];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new DomainException('Envie uma imagem JPEG, PNG ou WebP com extensão compatível.');
        }

        $image = getimagesize($temporaryPath);
        if ($image === false || ($image['mime'] ?? null) !== $mimeType) {
            throw new DomainException('O arquivo enviado não é uma imagem válida.');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento de imagens.');
        }

        $storedName = bin2hex(random_bytes(12)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        if (!move_uploaded_file($temporaryPath, $this->directory . DIRECTORY_SEPARATOR . $storedName)) {
            throw new RuntimeException('Não foi possível armazenar a imagem enviada.');
        }

        return $storedName;
    }

    public function publicUrl(?string $storedName, string $baseUrl): ?string
    {
        if ($storedName === null || $storedName === '' || basename($storedName) !== $storedName) {
            return null;
        }

        $extension = strtolower(pathinfo($storedName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
            return null;
        }
        if (!is_file($this->directory . DIRECTORY_SEPARATOR . $storedName)) {
            return null;
        }

        return $baseUrl . '/uploads/products/' . rawurlencode($storedName);
    }

    public function deleteManaged(?string $storedName): void
    {
        if ($storedName === null || $storedName === '' || basename($storedName) !== $storedName) {
            return;
        }

        $directory = realpath($this->directory);
        $file = realpath($this->directory . DIRECTORY_SEPARATOR . $storedName);
        if (
            $directory !== false
            && $file !== false
            && is_file($file)
            && str_starts_with($file, $directory . DIRECTORY_SEPARATOR)
        ) {
            unlink($file);
        }
    }
}