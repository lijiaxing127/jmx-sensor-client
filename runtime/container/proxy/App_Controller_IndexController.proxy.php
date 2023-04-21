<?php

declare (strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Exception\ParallelExecutionException;
use Hyperf\Utils\Coroutine;
use Hyperf\Utils\Parallel;
class IndexController extends AbstractController
{
    use \Hyperf\Di\Aop\ProxyTrait;
    use \Hyperf\Di\Aop\PropertyHandlerTrait;
    /**
     * @var \Hyperf\Guzzle\ClientFactory
     */
    private $clientFactory;
    public function __construct(ClientFactory $clientFactory)
    {
        $this->__handlePropertyHandler(__CLASS__);
        $this->clientFactory = $clientFactory;
    }
    public function index()
    {
        return ['method' => 1, 'message' => "Hello {1}."];
        $data = Db::table('sensor_data')->where('status', 0)->limit(10)->orderBy('collect_time', 'desc')->get();
        $sum = count($data);
        $results = [];
        $parallel = new Parallel(5);
        if ($sum > 0) {
            foreach ($data as $v) {
                $url = "http://10.168.1.179:9501/environmental/sensor/add";
                $client = new Client();
                $response = $client->request('POST', $url, ['headers' => ['token' => 'this is token'], 'multipart' => [['name' => 'id', 'contents' => $v->id], ['name' => 'sn', 'contents' => $v->sn], ['name' => 'data', 'contents' => $v->data], ['name' => 'collect_time', 'contents' => $v->collect_time]]]);
                var_dump($response);
                var_dump(3333333);
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
        return ['method' => 1, 'message' => "Hello {1}."];
    }
}