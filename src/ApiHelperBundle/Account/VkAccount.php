<?php

namespace ApiHelperBundle\Account;

/**
 * Class VkAccount.
 */
class VkAccount extends AbstractAccount
{
    /**
     * {@inheritdoc}
     */
    public function init(array $data)
    {
        if (isset($data['user_id'])) {
            $this->id = $data['user_id'];
            $this->loaded[] = 'id';
        }

        if (isset($data['email'])) {
            $this->email = $data['email'];
            $this->loaded[] = 'email';
        }

        return parent::init($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        $options = ['id', 'first_name', 'last_name', 'nickname', 'link', 'gender', 'birthday', 'location', 'picture'];
        if (in_array($option, $options)) {
            $fields = ['nickname', 'domain', 'sex', 'bdate', 'city', 'common_count', 'has_photo', 'photo_max_orig'];
            $data = $this->client->request('users.get', ['fields' => implode(',', $fields)])['response'][0];
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['id'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = 'https://vk.com/'.$data['domain'];

            if (!empty($data['nickname'])) {
                $this->nickname = $data['nickname'];
            }

            if (!empty($data['sex'])) {
                $this->gender = $data['sex'] == 1 ? 'female' : 'male';
            }

            if (!empty($data['bdate'])) {
                $this->birthday = new \DateTime($data['bdate'].(strlen($data['bdate']) < 8 ? '.0000' : ''));
            }

            if (!empty($data['city']['title'])) {
                $this->location = $data['city']['title'];
            }

            if ($data['has_photo']) {
                $this->picture = $data['photo_max_orig'];
            }

            if (0 == $data['common_count']) {
                $this->loaded[] = 'friends';
            }
        } elseif ('friends' === $option) {
            $this->friends = $this->client->request('friends.get')['items'];
            $this->loaded[] = 'friends';
        } else {
            $this->loaded[] = $option;
        }
    }
}
