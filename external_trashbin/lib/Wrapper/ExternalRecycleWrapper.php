<?php

declare(strict_types=1);

namespace OCA\External_Trashbin\Wrapper;

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Server;
use Psr\Log\LoggerInterface;

class ExternalRecycleWrapper extends Wrapper {
	private string $recycleRoot = '.recycle';
	private LoggerInterface $logger;

	public function __construct($parameters) {
		parent::__construct($parameters);
        $this->logger = Server::get(LoggerInterface::class);
	}

	public function unlink(string $path): bool {
		if (!$this->isLocalStorage() || $this->isInRecycle($path)) {
			return $this->storage->unlink($path);
		}

		$dest = $this->getRecyclePath($path);
		if (!$this->ensureDirForPath($dest)) {
			$this->logger->error("Failed to create recycle directory for $dest");
			return false;
		}

		if ($this->storage->file_exists($dest) || $this->storage->is_dir($dest)) {
			$dest = $this->uniqueDestination($dest);
		}

		try {
			if ($this->storage->rename($path, $dest)) {
				return true;
			}
		} catch (\Throwable $e) {
			$this->logger->warning("Failed to move $path to recycle using rename, trying to copy...");
		}

		if ($this->storage->copy($path, $dest)) {
			return $this->storage->unlink($path);
		}

		$this->logger->error("Failed to move $path to recycle");
		return false;
	}

	public function rmdir(string $path): bool {
		if (!$this->isLocalStorage() || $this->isInRecycle($path)) {
			return $this->storage->rmdir($path);
		}

		$dest = $this->getRecyclePath($path);
		if (!$this->ensureDirForPath($dest)) {
			$this->logger->error("Failed to create recycle directory for $dest");
			return false;
		}

		if ($this->storage->is_dir($dest) || $this->storage->file_exists($dest)) {
			$dest = $this->uniqueDestination($dest);
		}

		return $this->storage->rename($path, $dest);
	}

	private function isLocalStorage(): bool {
		if (!$this->instanceOfStorage(\OC\Files\Storage\Local::class)) {
			return false;
		}
		if (!$this->isLocal()) {
			return false;
		}
		if (!str_starts_with($this->storage->getId(), 'local::')) {
			return false;
		}
		return true;
	}

	private function normalize(string $path): string {
		return ltrim($path, '/');
	}

	private function isInRecycle(string $path): bool {
		$path = $this->normalize($path);
		return $path === $this->recycleRoot || strpos($path, $this->recycleRoot . '/') === 0;
	}

	private function getRecyclePath(string $path): string {
		$path = $this->normalize($path);
		return $this->recycleRoot . '/' . $path;
	}

	private function ensureDirForPath(string $filePath): bool {
		$dir = dirname($filePath);
		if ($dir === '.' || $dir === '') {
			return true;
		}
		$parts = explode('/', $dir);
		$acc = '';
		foreach ($parts as $p) {
			$acc = $acc === '' ? $p : ($acc . '/' . $p);
			if (!$this->storage->is_dir($acc)) {
				if (!$this->storage->mkdir($acc)) {
					return false;
				}
			}
		}
		return true;
	}

	private function uniqueDestination(string $dest): string {
		$ts = time();
		$suffix = '-deleted-' . $ts . '-' . random_int(1000, 9999);
		$pi = pathinfo($dest);
		if (isset($pi['extension']) && $pi['extension'] !== '') {
			$name = $pi['filename'] . $suffix . '.' . $pi['extension'];
			$new = ($pi['dirname'] === '.') ? $name : ($pi['dirname'] . '/' . $name);
		} else {
			$new = $dest . $suffix;
		}
		$tries = 0;
		while (($this->storage->file_exists($new) || $this->storage->is_dir($new)) && $tries < 10) {
			$tries++;
			$new .= '-' . random_int(10, 99);
		}
		return $new;
	}
}
