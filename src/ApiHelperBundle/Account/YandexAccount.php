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
 * Class YandexAccount.
 */
class YandexAccount extends AbstractAccount
{
    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        $options = ['id', 'first_name', 'last_name', 'nickname', 'email', 'birthday', 'gender', 'picture'];
        if (in_array($option, $options, true)) {
            $data = $this->client->request('info');
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['id'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->nickname = $data['login'];
            $this->email = $data['default_email'];

            if (!empty($data['birthday'])) {
                $this->birthday = new \DateTime($data['birthday']);
            }

            if (!empty($data['sex']) && in_array($data['sex'], ['male', 'female'], true)) {
                $this->gender = $data['sex'];
            }

            if (!$data['is_avatar_empty']) {
                $this->picture = 'https://avatars.yandex.net/get-yapic/'.$data['default_avatar_id'].'/islands-200';
            }
        } else {
            $this->loaded[] = $option;
        }
    }
}
