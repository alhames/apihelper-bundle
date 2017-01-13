<?php

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
        if (in_array($option, ['id', 'first_name', 'last_name', 'nickname', 'email', 'birthday', 'gender'])) {
            $data = $this->client->request('info');

            $this->id = $data['id'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->nickname = $data['login'];
            $this->email = $data['default_email'];

            if (!empty($data['birthday'])) {
                $this->birthday = new \DateTime($data['birthday']);
            }
            if (!empty($data['sex']) && in_array($data['sex'], ['male', 'female'])) {
                $this->gender = $data['sex'];
            }
        }
    }
}
