<?php

namespace App\Task;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Exception\ParallelExecutionException;
use Hyperf\Utils\Coroutine;
use Hyperf\Utils\Parallel;
use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;
/**
 * @Crontab(name="Foo", rule="* * * * *", callback="execute", memo="这是一个示例的定时任务")
 */
class FooTask
{
    use \Hyperf\Di\Aop\ProxyTrait;
    use \Hyperf\Di\Aop\PropertyHandlerTrait;
    function __construct()
    {
        $this->__handlePropertyHandler(__CLASS__);
    }
    /**
     * @Inject()
     * @var \Hyperf\Contract\StdoutLoggerInterface
     */
    private $logger;
    public function execute()
    {
        $this->logger->info('定时上传没有处理的数据:' . date('Y-m-d H:i:s', time()));
        $data = Db::table('sensor_data')->where('status', 0)->limit(100)->orderBy('collect_time', 'desc')->get();
        $sum = count($data);
        $results = [];
        $parallel = new Parallel(5);
        if ($sum > 0) {
            foreach ($data as $v) {
                $parallel->add(function () use($v) {
                    //                    $url = 'http://sensor.jinshenagr.com/api/sensorAdd';
                    //                    $url = "http://10.168.1.179:9501/environmental/sensor/add";
                    $url = "http://demo.jinshenagr.com/prod/environmental/sensor/add";
                    $client = new Client();
                    $response = $client->request('POST', $url, ['headers' => ['token' => 'this is token'], 'multipart' => [['name' => 'id', 'contents' => $v->id], ['name' => 'sn', 'contents' => $v->sn], ['name' => 'data', 'contents' => $v->data], ['name' => 'collect_time', 'contents' => $v->collect_time]]]);
                    $data = json_decode($response->getBody(), true);
                    $id = $v->id;
                    if (isset($data['error']) && $data['error'] == 0) {
                        $saveData = ['status' => 1];
                        if (Db::table('sensor_data')->where('id', $id)->update($saveData)) {
                            return 1;
                        } else {
                            return 2;
                        }
                    } else {
                        return 3;
                    }
                });
            }
        }
        try {
            $results = $parallel->wait();
        } catch (ParallelExecutionException $e) {
            //            var_dump($e->getResults());  //获取协程中的返回值。
            //            var_dump($e->getThrowables()); //获取协程中出现的异常。
        }
        $count = array_count_values($results);
        echo "本次请求总数据个数:" . $sum . PHP_EOL;
        echo "更新数据成功个数:" . (isset($count[1]) ? $count[1] : 0) . PHP_EOL;
        echo "更新数据失败个数:" . (isset($count[2]) ? $count[2] : 0) . PHP_EOL;
        echo "请求错误数据个数:" . (isset($count[3]) ? $count[3] : 0) . PHP_EOL;
    }
}