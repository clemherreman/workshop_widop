<?php
namespace Workshop;

class DatadogCollector
{
    /** @var string */
    private $host;

    /** @var string */
    private $port;

    /** @var string */
    private $prefix;

    /** @var array */
    private $data = array();

    /** @var string[] */
    private $defaultTags;

    /**
     * @param string $host
     * @param string $port
     * @param string $prefix
     * @param string $clientSystem
     */
    public function __construct($host, $port = '8125', $prefix = '', $defaultTags = array())
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->defaultTags = $defaultTags;
    }

    /**
     * {@inheritDoc}
     */
    public function timing($variable, $time, $tags = array())
    {
        $this->data[] = sprintf('%s:%s|ms%s', $variable, ($time * 1000), $this->buildTagString($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function increment($variable, $tags = array())
    {
        $this->data[] = $variable . ':1|c' . $this->buildTagString($tags);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($variable, $tags = array())
    {
        $this->data[] = $variable . ':-1|c' . $this->buildTagString($tags);
    }

    /**
     * {@inheritDoc}
     */
    public function measure($variable, $value, $tags = array())
    {
        $this->data[] = sprintf('%s:%s|c%s', $variable, $value, $this->buildTagString($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function gauge($variable, $value, $tags = array())
    {
        $this->data[] = sprintf('%s:%s|g%s', $variable, $value, $this->buildTagString($tags));
    }

    /**
     * {@inheritDoc}
     */
    public function histogram($variable, $value, $tags = array())
    {
        $this->data[] = sprintf('%s:%s|h%s', $variable, $value, $this->buildTagString($tags));
    }

    /**
     * Given a key/value map of metric tags, builds them into a
     * DogStatsD tag string and returns the string.
     *
     * @param array
     * @return string
     */
    private function buildTagString($tags)
    {
        $results = array();

        $tags = array_merge($this->defaultTags, $tags);

        foreach ($tags as $key => $value) {
            $results[] = sprintf('%s:%s', $key, $value);
        }

        $tagString = implode(',', $results);

        if (strlen($tagString)) {
            $tagString = sprintf('|#%s', $tagString);
        }

        return $tagString;
    }

    /**
     * {@inheritDoc}
     */
    public function flush()
    {
        if (!$this->data) {
            return;
        }

        $fp = fsockopen('udp://' . $this->host, $this->port, $errno, $errstr, 1.0);

        if (!$fp) {
            return;
        }

        $level = error_reporting(0);
        foreach ($this->data as $line) {
            fwrite($fp, $this->prefix.$line);
        }
        error_reporting($level);

        fclose($fp);

        $this->data = array();
    }
}
