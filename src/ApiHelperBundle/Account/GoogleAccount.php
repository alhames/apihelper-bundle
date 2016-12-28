<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelperBundle\Account;

/**
 * Class GoogleAccount.
 */
class GoogleAccount extends AbstractAccount
{
    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        if (in_array($option, ['id', 'first_name', 'last_name', 'link', 'email', 'nickname', 'gender'], true)) {
            $data = $this->client->request('userinfo/v2/me');

            $this->id = $data['id'];
            $this->firstName = $data['given_name'];
            $this->lastName = $data['family_name'];
            $this->link = isset($data['link']) ? $data['link'] : '';
            $this->email = !empty($data['email']) ? $data['email'] : '';
            $this->gender = (!empty($data['gender']) && in_array($data['gender'], ['male', 'female'], true)) ? $data['gender'] : '';

            $pattern = '#^'.preg_quote($this->firstName, '#').' “(.+?)” '.preg_quote($this->lastName, '#').'$#iu';
            $this->nickname = preg_match($pattern, $data['name'], $matches) ? $matches[1] : '';
        }
    }
}
