<?php

namespace ApiHelperBundle\Account;

/**
 * Class OkAccount.
 */
class OkAccount extends AbstractAccount
{
    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        $options = ['id', 'first_name', 'last_name', 'email', 'link', 'birthday', 'gender', 'location', 'picture'];
        if (in_array($option, $options)) {
            $fields = ['uid',  'first_name', 'last_name', 'gender', 'birthday', 'location', 'email', 'pic_full'];
            $data = $this->client->request('users/getCurrentUser', ['fields' => implode(',', $fields)]);
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['uid'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = 'https://ok.ru/profile/'.$data['uid'];

            if (isset($data['email'])) {
                $this->email = $data['email'];
            }

            if (!empty($data['birthday'])) {
                $this->birthday = new \DateTime((strlen($data['birthday']) > 5 ? '' : '0000-').$data['birthday']);
            }

            if (!empty($data['gender']) && in_array($data['gender'], ['male', 'female'])) {
                $this->gender = $data['gender'];
            }

            if (!empty($data['location']['city'])) {
                $this->location = $data['location']['city'];
            }

            if (!empty($data['pic_full'])) {
                $this->picture = $data['pic_full'];
            }
        } elseif ('friends' === $option) {
            $this->friends = $this->client->request('friends/get');
            $this->loaded[] = 'friends';
        } else {
            $this->loaded[] = $option;
        }
    }
}
