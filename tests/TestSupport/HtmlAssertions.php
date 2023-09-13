<?php

namespace Tests\TestSupport;

use DOMDocument;
use Illuminate\Support\HtmlString;

trait HtmlAssertions {

    protected function assertDomEquals($expected, $actual) {
        if ($expected instanceof HtmlString) {
            $expected = $expected->toHtml();
        }
        if ($actual instanceof HtmlString) {
            $actual = $actual->toHtml();
        }

        $expDoc = new DOMDocument();
        $actDoc = new DOMDocument();

        $this->assertTrue($expDoc->loadHtml($expected) && $actDoc->loadHtml($actual));

        $this->assertEquals(
            $expDoc->C14N(),
            $actDoc->C14N()
        );
    }

    protected function assertHtmlEquals($str, $result) {
        if ($result instanceof HtmlString) {
            $result = $result->toHtml();
        }

        $this->assertEquals($str, $result);
    }

    protected function assertSeeTag($tag, $result) {
        $this->assertMatchesRegularExpression('/\<' . $tag . '([^a-z]|\/\>)/', $result);
    }

    protected function assertSeeTagClose($tag, $result) {
        $this->assertMatchesRegularExpression('/<?\/' . $tag . '>/', $result);
    }

    protected function assertValidHtmlId($result, $attr = 'id') {
        if ($result instanceof HtmlString) {
            $result = $result->toHtml();
        }

        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXml($result));

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z][-_:.A-Za-z0-9]*$/',
            $doc->firstChild->attributes->getNamedItem($attr)->value
        );
    }
}
