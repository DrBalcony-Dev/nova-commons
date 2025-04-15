<?php

/**
 * Validate namespace consistency with PSR-4 autoloading structure
 */

$errors = [];
$baseNamespace = 'DrBalcony\\NovaCommon\\';
$srcPath = __DIR__ . '/src';

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcPath)) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getRealPath());

        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $matches);
        if (empty($matches)) {
            continue;
        }
        $namespace = $matches[1];

        // Extract class/interface/trait name
        preg_match('/\s+(class|interface|trait)\s+(\w+)/', $content, $classMatches);
        if (empty($classMatches)) {
            continue; // Not a class file
        }
        $className = $classMatches[2];

        // Calculate expected namespace based on path
        $relativePath = dirname($file->getRealPath());
        $relativePath = substr($relativePath, strlen($srcPath) + 1); // +1 for trailing slash
        $relativePath = str_replace('/', '\\', $relativePath);
        
        // If file is directly in src/ directory, relativePath will be empty
        $expectedNamespace = $baseNamespace . ($relativePath ? $relativePath : '');

        // Compare namespaces
        if ($namespace !== $expectedNamespace) {
            $errors[] = sprintf(
                "File %s has namespace '%s' but should be '%s' based on PSR-4 autoloading",
                $file->getPathname(),
                $namespace,
                $expectedNamespace
            );
        }
    }
}

if (!empty($errors)) {
    echo "NAMESPACE VALIDATION FAILED!\n";
    echo "The following files have incorrect namespaces:\n\n";
    
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    
    echo "\nPlease fix these namespace issues before committing.\n";
    exit(1);
}

echo "✅ All namespaces are valid and follow PSR-4 autoloading structure.\n";
exit(0); 