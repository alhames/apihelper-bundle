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
        $options = ['id', 'first_name', 'last_name', 'link', 'email', 'nickname', 'gender', 'picture'];
        if (in_array($option, $options, true)) {
            $data = $this->client->request('userinfo/v2/me');
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['id'];
            $this->firstName = $data['given_name'];
            $this->lastName = $data['family_name'];

            if (isset($data['link'])) {
                $this->link = $data['link'];
            }

            if (isset($data['email'])) {
                $this->email = $data['email'];
            }

            if (isset($data['gender']) && in_array($data['gender'], ['male', 'female'], true)) {
                $this->gender = $data['gender'];
            }

            $pattern = '#^'.preg_quote($this->firstName, '#').' “(.+?)” '.preg_quote($this->lastName, '#').'$#iu';
            if (preg_match($pattern, $data['name'], $matches)) {
                $this->nickname = $matches[1];
            }

            if (isset($data['picture'])) {
                $this->picture = $data['picture'];
            }
        } else {
            $this->loaded[] = $option;
        }
    }
}
