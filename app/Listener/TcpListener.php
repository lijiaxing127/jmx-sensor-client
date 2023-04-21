<?php
namespace App\Listener;

use App\Event\TcpBefore;
use App\Event\TcpAfter;
use App\Event\SensorStatus;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Context;
use Hyperf\HttpServer\Request;
use Hyperf\HttpServer\Response;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Contract\RequestInterface;


class TcpListener implements ListenerInterface
{

    /**
     * @var ResponseInterface
     */
    protected $response;


    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function listen(): array
    {
        // 返回一个该监听器要监听的事件数组，可以同时监听多个事件
        return [
            TcpBefore::class,
            TcpAfter::class,
            SensorStatus::class,
        ];
    }

//    /**
//     * @param TcpBefore $event
//     */

    /**
     * @param ResponseInterface $response
     */
    public function process(object $event)
    {
        // 事件触发后该监听器要执行的代码写在这里，比如该示例下的发送用户注册成功短信等
        if ($event instanceof TcpBefore){
            $id = Db::table('sensor_data')->insertGetId(
                   ['sn' => $event->sn,
                    'data' => $event->data,
                    'collect_time' => date('Y-m-d H:i:s')]
            );
            $event->id = $id;
        }
        if ($event instanceof TcpAfter){
            $data = Db::table('sensor_data')->find($event->id);
            $event->data  = json_encode($data);
        }
        if ($event instanceof SensorStatus){
            $data = json_decode($event->data,true);
            $id =$event->id;
            if (isset($data['error'])&&$data['error']==0){
                $saveData = [
                    'status'=>1,
                ];
            Db::table('sensor_data')->where('id', $id)->update($saveData);
            }
        }
    }
}