<?php

namespace Instapp\Service;

use InstagramAPI\Response\Model\Item;
use Instapp\Exception\MaxLikeCountException;
use Instapp\Instapp;
use Instapp\Macro\LikeAllPostsInTimeline;
use Instapp\Traits\Wait;

class Like
{
    use Wait;

    /**
     * @var int
     */
    protected $likeCount = 0;

    /**
     * @var int
     */
    public $maxLikeCount = 1000;

    /**
     * @var \DateTime
     */
    protected $startedAt;

    /**
     * @var Instapp
     */
    protected $app;

    /**
     * Like constructor.
     * @param Instapp $app
     */
    public function __construct(Instapp $app)
    {
        $this->app = $app;
        $this->startedAt = new \DateTime();
    }

    /**
     * @param Item $item
     */
    public function likeMedia(Item $item)
    {
        if ($this->likeCount >= $this->maxLikeCount)
        {
            try {
                throw new MaxLikeCountException("Max like count ({$this->maxLikeCount}) was reached.");
            } catch (MaxLikeCountException $e) {
                $this->app['logger']->add($e->getMessage());
                return;
            } finally {
                $this->status();
                return;
            }
        }

        $this->app['api']->media->like($item->id);

        $this->app['logger']->add(sprintf(
            '(%s) - `%s - instagram.com/p/%s` - liked!',
            ++$this->likeCount,
            $item->user->username,
            $item->code
        ));

        $this->wait();
    }

    /**
     * @return array
     */
    public function status()
    {
        $result = [
            'liked'     => $this->likeCount,
            'time'      => (new \DateTime())->diff($this->startedAt)
        ];

        $this->app['logger']->end(sprintf(
            '%s post liked in %s hours, %s minutes',
            $result['liked'],
            $result['time']->h,
            $result['time']->i
        ));

        return $result;
    }

    # MACROS

    public function likeTimeline()
    {
        $this->app['logger']->start('Scrolling down :)');
        return (new LikeAllPostsInTimeline($this->app))->run();
    }
}