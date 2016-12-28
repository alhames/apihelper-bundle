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
 * Class FacebookAccount.
 */
class FacebookAccount extends AbstractAccount
{
    /**
     * @param string $option
     */
    protected function load($option)
    {
        $userFields = ['id', 'email', 'link', 'gender', 'first_name', 'last_name'];
        if (in_array($option, $userFields, true)) {
            $data = $this->client->request('me', ['fields' => implode(',', $userFields)]);
            $this->id = $data['id'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = $data['link'];
            $this->email = !empty($data['email']) ? $data['email'] : '';

            if (!empty($data['gender'])) {
                if (in_array($data['gender'], ['male', 'мужской'], true)) {
                    $this->gender = 'male';
                } elseif (in_array($data['gender'], ['female', 'женский'], true)) {
                    $this->gender = 'female';
                }
            } else {
                $this->gender = '';
            }
        }
    }
}
