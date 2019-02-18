<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components;

use Yii;
use yii\base\UserException;
use yii\filters\RateLimiter;
use yii\filters\RateLimitInterface;
use yii\web\Request;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

use common\helpers\ToolsUtil;
use common\helpers\Util;

/**
 * RateLimiter implements a rate limiting algorithm based on the [leaky bucket algorithm](http://en.wikipedia.org/wiki/Leaky_bucket).
 *
 * You may use RateLimiter by attaching it as a behavior to a controller or module, like the following,
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'rateLimiter' => [
 *             'class' => frontend\components\RequestRateLimiter::className(),
 *         ],
 *     ];
 * }
 * ```
 *
 * When the user has exceeded his rate limit, RateLimiter will throw a [[TooManyRequestsHttpException]] exception.
 *
 * Note that RateLimiter requires [[user]] to implement the [[RateLimitInterface]]. RateLimiter will
 * do nothing if [[user]] is not set or does not implement [[RateLimitInterface]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RequestRateLimiter extends RateLimiter implements RateLimitInterface {
    public $enableRateLimitHeaders = false;

    private $controllerId = '';
    private $actionId = '';
    private $request_ip = '';

    public function beforeAction($action) {
        Yii::trace('Check rate limit', __METHOD__);
        $this->checkRateLimitVisit(
            $this->request ? : Yii::$app->getRequest(),
            $this->response ? : Yii::$app->getResponse(),
            $action
        );
        return true;
    }

    /**
     * Returns the maximum number of allowed requests and the window size.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @return array 返回在单位时间内允许的请求的最大数目，例如，[10, 60] 表示在60秒内最多请求10次。
     */
    public function getRateLimit($request, $action) {
        $rate_file = Util::loadConfig('request_rate_limit');
        $rate_limit = isset($rate_file[$this->controllerId][$this->actionId])
            ? $rate_file[$this->controllerId][$this->actionId]
            : [30, 1];
        return $rate_limit;
    }

    /**
     * Loads the number of allowed requests and the corresponding timestamp from a persistent storage.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @return array 返回剩余的允许的请求数和最新一次更新的时间戳数 The first element is the number of allowed requests,
     * and the second element is the corresponding UNIX timestamp.
     */
    public function loadAllowance($request, $action) {
        return [];
    }

    /**
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @param integer $allowance the number of allowed requests remaining.
     * @param integer $timestamp the current timestamp.
     */
    public function saveAllowance($request, $action, $allowance, $timestamp) {
        $remain_rate = Yii::$app->redis->executeCommand('INCR',[$this->controllerId . '-' . $this->actionId . '-remain-' . $this->request_ip]);
        return $remain_rate;
    }


    /**
     * Checks whether the rate limit exceeds.
     * @param RateLimitInterface $user the current user
     * @param Request $request
     * @param Response $response
     * @param \yii\base\Action $action the action to be executed
     * @throws TooManyRequestsHttpException if rate limit exceeds
     */
    public function checkRateLimitVisit($request, $response, $action) {
        $path_array = explode('/', $request->getPathInfo());
        if (count($path_array) == 2) {
            list($controllerId, $actionId) = $path_array;
        }
        else if (count($path_array) == 3) {
            list($moudleId, $controllerId, $actionId) = $path_array;
        }
        else {
            return true;
        }

        $this->actionId = $actionId;
        $this->controllerId = $controllerId;
        $this->request_ip = ToolsUtil::getIp();

        list ($limit, $window) = $this->getRateLimit($request, $action);
        if ($window == 0) {
            return true;
        }

        $allowance = $this->saveAllowance($request, $action, 0, 0);
        if ($allowance == 1) {
            \yii::$app->redis->executeCommand('EXPIRE', [$this->controllerId . '-' . $this->actionId . '-remain-' . $this->request_ip, $window]);
        }

        if ($allowance > $limit) {
            $this->addRateLimitHeaders($response, $limit, 0, $window);
            throw new TooManyRequestsHttpException($this->errorMessage);
        }
        else {
            $this->addRateLimitHeaders($response, $limit, $limit - $allowance, (int) (($limit - $allowance) * $window / $limit));
        }
    }

    /**
     * Adds the rate limit headers to the response
     * @param Response $response
     * @param integer $limit the maximum number of allowed requests during a period
     * @param integer $remaining the remaining number of allowed requests within the current period
     * @param integer $reset the number of seconds to wait before having maximum number of allowed requests again
     */
    public function addRateLimitHeaders($response, $limit, $remaining, $reset) {
        if ($this->enableRateLimitHeaders) {
            $response->getHeaders()
                ->set('X-Rate-Limit-Limit', $limit)
                ->set('X-Rate-Limit-Remaining', $remaining)
                ->set('X-Rate-Limit-Reset', $reset);
        }
    }
}
