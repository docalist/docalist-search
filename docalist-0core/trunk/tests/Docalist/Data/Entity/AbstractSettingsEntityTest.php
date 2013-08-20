<?php

namespace Docalist\Tests\Data\Entity;

use WP_UnitTestCase;

use Docalist\Data\Entity\AbstractSettingsEntity;

class Config extends AbstractSettingsEntity {
    protected function loadSchema() {
        return array(
            'server' => array('default' => 'localhost'),
            'port' => array(
                'type' => 'int',
                'default' => 9000,
            ),
            'credentials' => array(
                'fields' => array('login','password'),
            ),
        );
    }
}

class AbstractSettingsEntityTest extends WP_UnitTestCase {
    protected $key = 'SettingsEntityTest';

    public function setUp() {
        // garantit que la clé n'existe pas au début de chaque test
        delete_option($this->key);
    }

    public function testDefault() {
        $config = new Config($this->key);

        $this->assertSame($config->server, 'localhost');
        $this->assertSame($config->port, 9000);
        $this->assertSame($config->credentials->login, '');
        $this->assertSame($config->credentials->password, '');
    }

    public function testSaveLoadReset() {
        $config = new Config($this->key);

        // Modifie la config
        $config->port = 9876;
        $config->credentials = array(
            'login' => 'mylogin',
            'password' => 'mypwd',
        );
        $config->save();

        $this->assertSame($config->server, 'localhost');
        $this->assertSame($config->port, 9876);
        $this->assertSame($config->credentials->login, 'mylogin');
        $this->assertSame($config->credentials->password, 'mypwd');

        // Vérifie que l'option a été enregistrée, qu'elle est json et que
        // seules les données modifiées ont été écrites (i.e. pas server)
        $option = get_option($this->key);
        $json = '{"port":9876,"credentials":{"login":"mylogin","password":"mypwd"}}';
        $this->assertSame($option, $json);

        // Recharge la config
        $config = new Config($this->key);

        // Vérifie qu'on a bien les valeurs modifiées
        $this->assertSame($config->server, 'localhost');
        $this->assertSame($config->port, 9876);
        $this->assertSame($config->credentials->login, 'mylogin');
        $this->assertSame($config->credentials->password, 'mypwd');

        // Réinitialise la config
        $config->reset();

        // Vérifie que la config est revenue à ses valeurs par défaut
        $this->assertSame($config->server, 'localhost');
        $this->assertSame($config->port, 9000);
        $this->assertSame($config->credentials->login, '');
        $this->assertSame($config->credentials->password, '');

        // Vérifie que l'option a été supprimée de la base
        $option = get_option($this->key);
        $this->assertSame($option, false);

    }

}