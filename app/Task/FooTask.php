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

    /**
     * @Inject()
     * @var \Hyperf\Contract\StdoutLoggerInterface
     */
    private $logger;

    public function execute()
    {

        $this->logger->info('定时上传没有处理的数据:'.date('Y-m-d H:i:s', time()));
        $db = \App\Model\Sqlite3::getInstance()->getDb();
        $data = $db->query('SELECT * FROM sensor_data where status = 0 order by  collect_time  desc limit 100');
        $results = [];
        $tmp = [];
        while ($row = $data->fetchArray()) {
            $tmp[]=$row;
        }
        foreach ($tmp as $row){
            $url = "http://demo.jinshenagr.com/prod/environmental/sensor/add";
            $client = new Client();
            $response = $client->request('POST', $url, [
                'headers' => [
                    'token' => 'this is token'
                ],
                'multipart' => [
                    [
                        'name' => 'id',
                        'contents' => $row['id']
                    ],
                    [
                        'name' => 'sn',
                        'contents' => $row['sn']
                    ],
                    [
                        'name' => 'data',
                        'contents' => $row['data']
                    ],
                    [
                        'name' => 'collect_time',
                        'contents' => $row['collect_time']
                    ],
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(),true);

            $id = $row['id'];
            if (isset($data['error'])&&$data['error']==0){
                if ($db->exec("UPDATE sensor_data SET status=1 WHERE id=$id")){
                    $results[]=  1;
                }else{
                    $results[]=   2;
                }
            }else{
                $results[]=   3;
            }
        }

        $count = array_count_values($results);
        echo "本次请求总数据个数:".count($results).PHP_EOL;
        echo "更新数据成功个数:".(isset($count[1])?$count[1]:0).PHP_EOL;
        echo "更新数据失败个数:".(isset($count[2])?$count[2]:0).PHP_EOL;
        echo "请求错误数据个数:".(isset($count[3])?$count[3]:0).PHP_EOL;

// 关闭数据库连接
//        $db->close();



    }
}
