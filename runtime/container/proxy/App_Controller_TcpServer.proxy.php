<?php

declare (strict_types=1);
namespace App\Controller;

use Hyperf\Contract\OnReceiveInterface;
use Hyperf\Server\Event\ConnectEvent;
use Psr\Container\ContainerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Event\TcpAfter;
use App\Event\TcpBefore;
use App\Event\SensorStatus;
use Hyperf\Utils\Context;
class TcpServer implements OnReceiveInterface
{
    use \Hyperf\Di\Aop\ProxyTrait;
    use \Hyperf\Di\Aop\PropertyHandlerTrait;
    function __construct()
    {
        $this->__handlePropertyHandler(__CLASS__);
    }
    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @Inject
     * @var ClientFactory
     */
    protected $clientFactory;
    protected $host = 'ws://ws.jinshenagr.com/sensor.io';
    //    protected $host = 'ws://10.168.1.179:9567/sensor.io';
    protected $clients = [];
    public function onReceive($server, int $fd, int $reactorId, string $data) : void
    {
        $sn = substr($data, 0, 10);
        $value = str_replace($sn, '', $data);
        $get_value = bin2hex($value);
        // 将2进制数据转换成16进制
        echo $sn . ':' . $get_value;
        if ($get_value && $sn && strlen($sn) == 10 && strlen($sn) > 5) {
            echo 'sn is ' . $sn . '     485 is ' . $get_value . PHP_EOL;
            $result = $this->eventDispatcher->dispatch(new TcpBefore($sn, $get_value));
            echo 'TcpBefore id is ' . $result->id . PHP_EOL;
            try {
                if (isset($this->clients[$fd])) {
                    $client = (array) $this->clients[$fd];
                    // 获取状态码
                    $statusCode = $client[array_keys($client)[1]]->statusCode;
                    if ($statusCode === 101) {
                        $TcpAfter = $this->eventDispatcher->dispatch(new TcpAfter($result->id));
                        $this->clients[$fd]->push($TcpAfter->data);
                        $msg = $this->clients[$fd]->recv(2);
                        // 获取文本数据：$res_msg->data
                        var_dump($msg->data);
                        $this->eventDispatcher->dispatch(new SensorStatus($result->id, $msg->data));
                        echo "连接成功！";
                    } else {
                        echo "连接失败！状态码为 {$statusCode}。";
                        echo "正在尝试重新连接！";
                        // 通过 ClientFactory 创建 Client 对象，创建出来的对象为短生命周期对象
                        $client = $this->clientFactory->create($this->host . '?sn=' . $sn, false);
                        $this->clients[$fd] = $client;
                    }
                } else {
                    // 通过 ClientFactory 创建 Client 对象，创建出来的对象为短生命周期对象
                    $client = $this->clientFactory->create($this->host . '?sn=' . $sn, false);
                    $this->clients[$fd] = $client;
                }
            } catch (\Throwable $e) {
                // 处理单个异常
                echo 'WebSocket 连接失败(2)：' . $e->getMessage() . "\n";
            }
        }
        $server->send($fd, 'rv' . $data);
    }
    //监听链接事件
    public function onConnect($server, int $fd)
    {
        echo "{$fd} client : connect\n";
    }
}