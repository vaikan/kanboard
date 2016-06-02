<?php

namespace Kanboard\Helper;

use Kanboard\Core\Base;

/**
 * Class MailHelper
 *
 * @package Kanboard\Helper
 * @author  Frederic Guillot
 */
class MailHelper extends Base
{
    /**
     * Get the mailbox hash from an email address
     *
     * @access public
     * @param  string  $email
     * @return string
     */
    public function getMailboxHash($email)
    {
        if (! strpos($email, '@') || ! strpos($email, '+')) {
            return '';
        }

        list($localPart, ) = explode('@', $email);
        list(, $identifier) = explode('+', $localPart);

        return $identifier;
    }

    /**
     * Filter mail subject
     *
     * @access public
     * @param  string $subject
     * @return string
     */
    public function filterSubject($subject)
    {
        $subject = str_replace('RE: ', '', $subject);
        $subject = str_replace('FW: ', '', $subject);

        return $subject;
    }
}
