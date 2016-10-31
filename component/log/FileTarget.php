<?php
/**
 * @描述 用于文件的方式记录日志，根据请求路由，每个请求一个日志文件
 * @时间 2016年10月18日 15:43:22
 * @作者 Maker.xing
 */

namespace component\log;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\Request;
use yii\log\Target;
use yii\log\Logger;

class FileTarget extends Target
{
	/**
	 * 日志文件滚动选项
	 * 默认允许滚动
	 */
	public $enableRotation = true;

	/**
	 * 记录日志的路径，字符串
	 */
	public $logFile;

	/**
	 * 每个请求记录日志文件的最多个数
	 * 默认是5个
	 */
	public $maxLogFiles = 5;

	/**
	 * 每个日志文件的大小限制
	 * 默认是10M
	 */
	public $maxFileSize = 10240;	//in kb

	/**
	 * 日志中要记录的额外信息
	 * 重写父类属性，默认为空
	 */
	public $logVars = [];

	/**
	 * 创建目录时候的默认权限
	 * 默认所属者和所属组的成员权限是读写，其他用户只能读取
	 */
	public $dirMode = 0775;

	/**
	 * 日志滚动的时候设置滚动模式，复制还是替换
	 * 默认是复制，在windows下面rename可能会出问题
	 */
	public $rotateByCopy = true;

	/**
	 * 初始化，实例化的时候会运行
	 */
    public function init()
    {
        parent::init();
        $logFileName = $this->getLogFileName();
        if ($this->logFile === null) {
            if(!$logFileName) {
                $this->logFile = Yii::$app->getRuntimePath() . '/logs/app.log';
            } else {
                $this->logFile = Yii::$app->getRuntimePath() . "/logs/" . $logFileName;
            }
        } else {
            $this->logFile = Yii::getAlias($this->logFile);
        }
        $logPath = dirname($this->logFile);
        if (!is_dir($logPath)) {
            FileHelper::createDirectory($logPath, $this->dirMode, true);
        }
        if ($this->maxLogFiles < 1) {
            $this->maxLogFiles = 1;
        }
        if ($this->maxFileSize < 1) {
            $this->maxFileSize = 1;
        }
    }

    /**
     * @描述 根据请求的路由生成文件的名称
     */
    private function getLogFileName()
    {
    	$request = Yii::$app->getRequest();
    	if($request instanceof Request)
    	{
    		$logFileName = $request->getPathInfo();
    		return $logFileName ? preg_replace("/\//", ".", $logFileName).".log" : "";
    	}

        return "";
    }


    /**
     * @描述 记录日志文件到文件中
     */
	public function export()
	{
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n\n\n";
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }

        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
	}

    /**
     * @描述 滚动日志文件
     */
    protected function rotateFiles()
    {
        $file = $this->logFile;
        for ($i = $this->maxLogFiles; $i >= 0; --$i) {
            // $i == 0 is the original log file
            $rotateFile = $file . ($i === 0 ? '' : '.' . $i);
            if (is_file($rotateFile)) {
                // suppress errors because it's possible multiple processes enter into this section
                if ($i === $this->maxLogFiles) {
                    @unlink($rotateFile);
                } else {
                    if ($this->rotateByCopy) {
                        @copy($rotateFile, $file . '.' . ($i + 1));
                        if ($fp = @fopen($rotateFile, 'a')) {
                            @ftruncate($fp, 0);
                            @fclose($fp);
                        }
                    } else {
                        @rename($rotateFile, $file . '.' . ($i + 1));
                    }
                }
            }
        }
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!is_string($text)) {
            $text = VarDumper::export($text);
        }
        $traces = [];
        if (isset($message[4])) {
            foreach($message[4] as $trace) {
                $traces[] = basename($trace['file']).":{$trace['line']}";
            }
        }
        return date('Y-m-d H:i:s', $timestamp) . " [$level][$category]".
                (empty($traces) ? '' : "[".implode("=>", $traces))."] $text";
    }
}
