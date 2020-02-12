<?php


namespace Yiisoft\View\Tests\Mocks;


use Yiisoft\View\WebView;

class WebViewPlaceholderMock extends WebView
{
    public function endPage($ajaxMode = false): void
    {
        $this->setPlaceholderSalt("" . time());
        parent::endPage($ajaxMode);
    }
}
