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
        $options = ['id', 'first_name', 'last_name', 'nickname', 'email', 'birthday', 'gender', 'picture'];
        if (in_array($option, $options)) {
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

            if (!empty($data['sex']) && in_array($data['sex'], ['male', 'female'])) {
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
