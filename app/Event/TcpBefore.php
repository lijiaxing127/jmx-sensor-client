<?php


namespace App\Event;

class TcpBefore
{
    // 建议这里定义成 public 属性，以便监听器对该属性的直接使用，或者你提供该属性的 Getter
    public $sn;
    public $data;

    public function __construct($sn,$data)
    {
        $this->sn = $sn;
        $this->data = $data;
    }
}