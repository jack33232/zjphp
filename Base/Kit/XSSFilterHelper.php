<?php
namespace ZJPHP\Base\Kit;

/**
 * PHP 富文本XSS过滤类
 *
 * @package XssHtml
 * @version 1.0.0
 * @link http://phith0n.github.io/XssHtml
 * @since 20140621
 * @copyright (c) Phithon All Rights Reserved
 *
 */
#
# Written by Phithon <root@leavesongs.com> in 2014 and placed in
# the public domain.
#
# phithon <root@leavesongs.com> 编写于20140621
# From: XDSEC <www.xdsec.org> & 离别歌 <www.leavesongs.com>
# Usage: 
# <?php
# require('xsshtml.class.php');
# $html = '<html code>';
# $xss = new XssHtml($html);
# $html = $xss->getHtml();
# ?\>
# 
# 需求：
# PHP Version > 5.0
# 浏览器版本：IE7+ 或其他浏览器，无法防御IE6及以下版本浏览器中的XSS
# 更多使用选项见 http://phith0n.github.io/XssHtml
class XSSFilterHelper
{
    private $m_dom;
    private $m_xss;
    private $m_ok;
    private $m_AllowAttr = array('title', 'src', 'href', 'id', 'class', 'style', 'width', 'height', 'alt', 'target', 'align');
    private $m_AllowTag = array('a', 'img', 'br', 'strong', 'b', 'code', 'pre', 'p', 'div', 'em', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'ul', 'ol', 'tr', 'th', 'td', 'hr', 'li', 'u');
    /**
     * 构造函数
     *
     * @param string $html 待过滤的文本
     * @param string $charset 文本编码，默认utf-8
     * @param array $AllowTag 允许的标签，如果不清楚请保持默认，默认已涵盖大部分功能，不要增加危险标签
     */

    public static function clean($html, $charset = 'utf-8', $AllowTag = array())
    {
        $newInstance = new static($html, $charset, $AllowTag);
        $result = $newInstance->getHtml();
        unset($newInstance);
        return $result;
    }

    public function __construct($html, $charset = 'utf-8', $AllowTag = array())
    {
        $this->m_AllowTag = empty($AllowTag) ? $this->m_AllowTag : $AllowTag;
        $this->m_xss = strip_tags($html, '<' . implode('><', $this->m_AllowTag) . '>');
        if (empty($this->m_xss)) {
            $this->m_ok = false;
            return ;
        }
        $this->m_xss = "<meta http-equiv=\"Content-Type\" content=\"text/html;charset={$charset}\"><nouse>" . $this->m_xss . "</nouse>";
        $this->m_dom = new DOMDocument();
        $this->m_dom->strictErrorChecking = false;
        $this->m_ok = @$this->m_dom->loadHTML($this->m_xss);
    }
    /**
     * 获得过滤后的内容
     */
    public function getHtml()
    {
        if (!$this->m_ok) {
            return '';
        }
        $nodeList = $this->m_dom->getElementsByTagName('*');
        for ($i = 0; $i < $nodeList->length; $i++) {
            $node = $nodeList->item($i);
            if (in_array($node->nodeName, $this->m_AllowTag)) {
                if (method_exists($this, "{$node->nodeName}Node")) {
                    call_user_func(array($this, "{$node->nodeName}Node"), $node);
                } else {
                    call_user_func(array($this, 'defaultNode'), $node);
                }
            }
        }
        $html = strip_tags($this->m_dom->saveHTML(), '<' . implode('><', $this->m_AllowTag) . '>');
        $html = preg_replace('/^\n(.*)\n$/s', '$1', $html);
        return $html;
    }
    private function trueUrl($url)
    {
        if (preg_match('#^https?://.+#is', $url)) {
            return $url;
        } else {
            return 'http://' . $url;
        }
    }
    private function getStyle($node)
    {
        if ($node->attributes->getNamedItem('style')) {
            $style = $node->attributes->getNamedItem('style')->nodeValue;
            $style = str_replace('\\', ' ', $style);
            $style = str_replace(array('&#', '/*', '*/'), ' ', $style);
            $style = preg_replace('#e.*x.*p.*r.*e.*s.*s.*i.*o.*n#Uis', ' ', $style);
            return $style;
        } else {
            return '';
        }
    }
    private function getLink($node, $att)
    {
        $link = $node->attributes->getNamedItem($att);
        if ($link) {
            return $this->trueUrl($link->nodeValue);
        } else {
            return '';
        }
    }
    private function setDomAttr($dom, $attr, $val)
    {
        if (!empty($val)) {
            $dom->setAttribute($attr, $val);
        }
    }
    private function setDomDefaultAttr($node, $attr, $default = '')
    {
        $o = $node->attributes->getNamedItem($attr);
        if ($o) {
            $this->setDomAttr($node, $attr, $o->nodeValue);
        } else if ($default !== '') {
            $this->setDomAttr($node, $attr, $default);
        }
    }
    private function commonDomAttr($node)
    {
        $list = array();
        foreach ($node->attributes as $attr) {
            if (!in_array(
                $attr->nodeName,
                $this->m_AllowAttr
            )) {
                $list[] = $attr->nodeName;
            }
        }
        foreach ($list as $attr) {
            $node->removeAttribute($attr);
        }
        $style = $this->getStyle($node);
        $this->setDomAttr($node, 'style', $style);
        $this->setDomDefaultAttr($node, 'title');
        $this->setDomDefaultAttr($node, 'id');
        $this->setDomDefaultAttr($node, 'class');
    }
    private function imgNode($node)
    {
        $this->commonDomAttr($node);
        $this->setDomDefaultAttr($node, 'src');
        $this->setDomDefaultAttr($node, 'width');
        $this->setDomDefaultAttr($node, 'height');
        $this->setDomDefaultAttr($node, 'alt');
        $this->setDomDefaultAttr($node, 'align');
    }
    private function aNode($node)
    {
        $this->commonDomAttr($node);
        $href = $this->getLink($node, 'href');
        $this->setDomAttr($node, 'href', $href);
        $this->setDomDefaultAttr($node, 'target', '_blank');
    }
    private function embedNode($node)
    {
        $this->commonDomAttr($node);
        $link = $this->getLink($node, 'src');
        $this->setDomAttr($node, 'src', $link);
        $this->setDomAttr($node, 'allowscriptaccess', 'never');
        $this->setDomDefaultAttr($node, 'width');
        $this->setDomDefaultAttr($node, 'height');
    }
    private function defaultNode($node)
    {
        $this->commonDomAttr($node);
    }
}
