<?php



namespace App\Event;

class TcpAfter
{
    // 建议这里定义成 public 属性，以便监听器对该属性的直接使用，或者你提供该属性的 Getter
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}