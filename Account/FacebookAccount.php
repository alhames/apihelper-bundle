<?php

/*
 * This file is part of the API Helper Bundle package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alhames\ApiHelperBundle\Account;

/**
 * Class FacebookAccount.
 */
class FacebookAccount extends AbstractAccount
{
    /**
     * @todo friends
     * @todo picture
     *
     * {@inheritdoc}
     */
    protected function load($option)
    {
        $options = ['id', 'email', 'link', 'gender', 'first_name', 'last_name', 'birthday', 'location'];
        if (in_array($option, $options, true)) {
            $data = $this->client->request('me', ['fields' => implode(',', $options)]);
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['id'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = $data['link'];

            if (isset($data['email'])) {
                $this->email = $data['email'];
            }

            if (isset($data['gender'])) {
                if (in_array($data['gender'], ['male', 'мужской'], true)) {
                    $this->gender = 'male';
                } elseif (in_array($data['gender'], ['female', 'женский'], true)) {
                    $this->gender = 'female';
                }
            }

            if (isset($data['birthday'])) {
                $this->birthday = new \DateTime($data['birthday']);
            }

            if (isset($data['location']['name'])) {
                $this->location = $data['location']['name'];
            }
        } else {
            $this->loaded[] = $option;
        }
    }
}
