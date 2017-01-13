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
        }

        if (isset($data['email'])) {
            $this->email = $data['email'];
        }

        return parent::init($data);
    }

    /**
     * @param string $option
     */
    protected function load($option)
    {
        if (in_array($option, ['first_name', 'last_name', 'nickname', 'link', 'gender', 'birthday', 'location'])) {
            $fields = ['nickname', 'screen_name', 'sex', 'bdate', 'city', 'common_count'];
            $data = $this->client->request('users.get', ['fields' => implode(',', $fields)])[0];

            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->nickname = !empty($data['nickname']) ? $data['nickname'] : '';
            $this->link = 'https://vk.com/'.$data['screen_name'];

            if (!empty($data['sex'])) {
                $this->gender = $data['sex'] == 1 ? 'female' : 'male';
            }

            if (!empty($data['bdate'])) {
                $birthday = explode('.', $data['bdate']);
                $birthdayString = isset($birthday[2]) ? $birthday[2] : '0000';
                $birthdayString .= '-'.str_pad($birthday[1], 2, '0', STR_PAD_LEFT);
                $birthdayString .= '-'.str_pad($birthday[0], 2, '0', STR_PAD_LEFT);
                $this->birthday = new \DateTime($birthdayString);
            }

            $this->location = !empty($data['city']['title']) ? $data['city']['title'] : '';

            if (0 == $data['common_count']) {
                $this->friends = [];
            }
        }

        if ('friends' === $option) {
            $this->friends = $this->client->request('friends.get')['items'];
        }
    }
}
