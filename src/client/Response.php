<?php


namespace Client;


class Response implements \ArrayAccess
{

    public  $statusCode;
    private $content = [];

    public function __construct($content, $statusCode = 200)
    {
        $this->statusCode = $statusCode;
        $this->setContent($content);
    }

    public function array(): array
    {
        return $this->content;
    }

    public function json(): string
    {
        return json_encode($this->content);
    }

    public function __toString()
    {
        return $this->json();
    }

    private function setContent($content)
    {
        if ($this->statusCode == 200) {
            $this->content = json_decode($content, true) ?: [];
            return;
        }

        $this->content = $this->error($content);
    }

    private function error($content): array
    {
        if ($this->statusCode == 200 || empty($content)) {
            return [];
        }
        $arrayContent = json_decode($content, true);
        if (is_array($arrayContent)) {
            return $arrayContent;
        }

        return [
            'code' => $this->statusCode,
            'msg'  => $content,
        ];
    }

    public function offsetExists($offset)
    {
        return isset($this->content[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->content[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        $this->content[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->content[$offset]);
    }
}