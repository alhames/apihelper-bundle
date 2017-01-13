<?php

namespace ApiHelperBundle\Account;

/**
 * Class MailRuAccount.
 */
class MailRuAccount extends AbstractAccount
{
    /**
     * {@inheritdoc}
     */
    public function init(array $data)
    {
        if (isset($data['x_mailru_vid'])) {
            $this->id = $data['x_mailru_vid'];
        }

        return parent::init($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        if (in_array($option, ['id', 'email', 'nickname', 'first_name', 'last_name', 'link', 'gender', 'birthday', 'location'])) {
            $data = $this->client->request('users.getInfo')[0];

            $this->id = $data['uid'];
            $this->email = $data['email'];
            $this->nickname = $data['nick'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = $data['link'];
            $this->gender = $data['sex'] ? 'female' : 'male';

            if (!empty($data['birthday'])) {
                $birthday = explode('.', $data['birthday']);
                $this->birthday = new \DateTime(
                    (isset($birthday[2]) ? $birthday[2] : '0000')
                    .'-'.str_pad($birthday[1], 2, '0', STR_PAD_LEFT)
                    .'-'.str_pad($birthday[0], 2, '0', STR_PAD_LEFT)
                );
            }

            if (!empty($data['location']['city']['name'])) {
                $this->location = $data['location']['city']['name'];
            }

            if (0 == $data['friends_count']) {
                $this->friends = [];
            }
        }

        if ('friends' === $option) {
            $this->friends = $this->client->request('friends.get', ['ext' => 0]);
        }
    }
}
