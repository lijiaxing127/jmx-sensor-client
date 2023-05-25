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
        
//        $db = new \SQLite3('sensor.db');
        $db = \App\Model\Sqlite3::getInstance()->getDb();
        // 事件触发后该监听器要执行的代码写在这里，比如该示例下的发送用户注册成功短信等
        if ($event instanceof TcpBefore){
            $collect_time = date('Y-m-d H:i:s');
//            sleep(0.02);
            $db->exec("INSERT INTO sensor_data (sn, `data`,status,collect_time) VALUES ('$event->sn', '$event->data',0,'$collect_time')");
            $event->id = $db->lastInsertRowID();
            echo 'insert id is '.$event->id .PHP_EOL;

        }
        if ($event instanceof TcpAfter){
            $result = $db->query('SELECT * FROM sensor_data where id ='.$event->id);
            $row = $result->fetchArray();
            $data = [];
            if ($row){
                $data= [
                    'id'=>$row['id'],
                    'sn'=>$row['sn'],
                    'data'=>$row['data'],
                    'collect_time'=>$row['collect_time'],
                ];
            }
            $event->data  = json_encode($data);
        }
        if ($event instanceof SensorStatus){
            $data = json_decode($event->data,true);
            $id =$event->id;
            if (isset($data['error'])&&$data['error']==0){
//                sleep(0.03);
                 $r = $db->exec("UPDATE sensor_data SET status=1 WHERE id= $id");
                 echo 'update is '.$r .PHP_EOL;
            }
        }
//        $db->close();
    }
}