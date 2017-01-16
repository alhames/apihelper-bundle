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
            $this->loaded[] = 'id';
        }

        return parent::init($data);
    }

    /**
     * {@inheritdoc}
     */
    protected function load($option)
    {
        $options = ['id', 'email', 'nickname', 'first_name', 'last_name', 'link', 'gender', 'birthday', 'location', 'picture'];
        if (in_array($option, $options)) {
            $data = $this->client->request('users.getInfo')[0];
            $this->loaded = array_merge($this->loaded, $options);

            $this->id = $data['uid'];
            $this->email = $data['email'];
            $this->nickname = $data['nick'];
            $this->firstName = $data['first_name'];
            $this->lastName = $data['last_name'];
            $this->link = $data['link'];
            $this->gender = $data['sex'] ? 'female' : 'male';

            if (!empty($data['birthday'])) {
                $this->birthday = new \DateTime($data['birthday'].(strlen($data['birthday']) < 8 ? '.0000' : ''));
            }

            if (!empty($data['location']['city']['name'])) {
                $this->location = $data['location']['city']['name'];
            }

            if ($data['has_pic']) {
                $this->picture = $data['pic_big'];
            }

            if (0 == $data['friends_count']) {
                $this->loaded[] = 'friends';
            }
        } elseif ('friends' === $option) {
            $this->friends = $this->client->request('friends.get', ['ext' => 0]);
            $this->loaded[] = 'friends';
        } else {
            $this->loaded[] = $option;
        }
    }
}
