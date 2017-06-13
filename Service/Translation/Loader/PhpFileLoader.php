<?php
namespace ZJPHP\Service\Translation\Loader;

use Symfony\Component\Translation\Loader\PhpFileLoader as SymfonyPhpFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Config\Resource\FileResource;

class PhpFileLoader extends SymfonyPhpFileLoader
{
    public function load($resource, $locale, $domain = 'messages')
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
        }

        $messages = $this->loadResource($resource);

        // empty resource
        if (null === $messages) {
            $messages = array();
        }

        // not an array
        if (!is_array($messages)) {
            throw new InvalidResourceException(sprintf('Unable to load file "%s".', $resource));
        }

        if ($domain !== 'messages') {
            $messages = array(
                $domain => $messages
            );
        }

        $catalogue = ArrayLoader::load($messages, $locale, $domain);

        if (class_exists('Symfony\Component\Config\Resource\FileResource')) {
            $catalogue->addResource(new FileResource($resource));
        }

        return $catalogue;
    }
}
