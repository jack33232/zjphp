<?php
namespace ZJPHP\Service\Translation;

use Symfony\Component\Translation\Translator;

class ZJTranslator extends Translator
{
    protected $activeDomain;

    public function setDomain($domain = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }
        $this->activeDomain = $domain;
    }

    public function getDomain()
    {
        return ($this->activeDomain) ? $this->activeDomain : 'messages';
    }

    public function addResource($format, $resource, $locale, $domain = null)
    {
        if (null === $domain) {
            $domain = $this->getDomain();
        }
        return parent::addResource($format, $resource, $locale, $domain);
    }

    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = $this->getDomain();
        }
        return parent::trans($id, $parameters, $domain, $locale);
    }

    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = $this->getDomain();
        }

        return parent::transChoice($id, $number, $parameters, $domain, $locale);
    }
}
