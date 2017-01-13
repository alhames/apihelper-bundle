<?php

namespace ApiHelperBundle\Account;

/**
 * Class OkAccount.
 */
class OkAccount extends AbstractAccount
{
    /**
     * @param string $option
     */
    protected function load($option)
    {
        if (in_array($option, ['id', 'first_name', 'last_name', 'email', 'link', 'birthday', 'gender', 'location'])) {
            $fields = ['uid',  'first_name', 'last_name', 'gender', 'birthday', 'location', 'email'];
            $data = $this->client->request('users.getCurrentUser', ['fields' => implode(',', $fields)]);

            $this->id = $data['uid'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->email = isset($data['email']) ? $data['email'] : '';
            $this->link = 'https://ok.ru/profile/'.$data['uid'];

            if (!empty($data['birthday'])) {
                $this->birthday = new \DateTime(
                    strlen($data['birthday']) > 5 ? $data['birthday'] : '0000-'.$data['birthday']
                );
            }

            if (!empty($data['gender']) && in_array($data['gender'], ['male', 'female'])) {
                $this->gender = $data['gender'];
            }

            if (!empty($data['location']['city'])) {
                $this->location = $data['location']['city'];
            }
        }

        if ('friends' === $option) {
            $this->friends = $this->client->request('friends.get');
        }
    }
}
