<?php

namespace NotificationCenter\Notification;

class Comment extends \NotificationCenter\Notification
{
    public function init()
    {
        add_action('wp_insert_comment', array($this, 'newComment'), 99, 2);
    }

    /**
     * Do notifications on insert comment hook
     * @param  int $commentId  The new comment's ID
     * @param  obj $commentObj WP_Comment object
     * @return void
     */
    public function newComment($commentId, $commentObj)
    {
        $notifiers = array();

        if ($commentObj->comment_parent > 0) {
            // Entity: Comment reply
            $parentComment = get_comment($commentObj->comment_parent);
            $notifiers[] = (int)$parentComment->user_id;
            $this->insertNotifications(1, $commentId, $notifiers, $commentObj->user_id);

            // Entity: Post thread contribution.
            $contributors = get_comments(array(
                                'parent' => $commentObj->comment_parent,
                                'author__not_in' => array($commentObj->user_id, (int)$parentComment->user_id)
                            ));
            if (!empty($contributors)) {
                $notifiers = array();
                foreach ($contributors as $key => &$contributor) {
                    // Continue if user does not exist
                    if (!$contributor->user_id) {
                        continue;
                    }

                    $notifiers[] = (int) $contributor->user_id;
                }
                $this->insertNotifications(2, $commentId, $notifiers, $commentObj->user_id);
            }
        }

        // Entity: New post comment
        $notifier = get_post_field('post_author', $commentObj->comment_post_ID);
        if (!in_array($notifier, $notifiers)) {
            $this->insertNotifications(0, $commentId, array((int) $notifier), $commentObj->user_id);
        }
    }
}
