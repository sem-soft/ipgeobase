<?php

use yii\db\Migration;

class m170228_151006_create_ipgeobase_structure extends Migration
{

    public function up()
    {
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=MyISAM';
        $this->createTable('{{%geo__base}}', [
            'long_ip1' => $this->bigInteger(20)->notNull(),
            'long_ip2' => $this->bigInteger(20)->notNull(),
            'ip1' => $this->string(16)->notNull(),
            'ip2' => $this->string(16)->notNull(),
            'country' => $this->string(2)->notNull(),
            'city_id' => $this->string(10)->notNull(),
            ], $tableOptions);
        $this->createIndex('idx_geo__base_long_ips', '{{%geo__base}}', [
            'long_ip1',
            'long_ip2'
        ]);

        $this->createTable('{{%geo__cities}}', [
            'city_id' => $this->primaryKey(),
            'city' => $this->string(128)->notNull(),
            'region' => $this->string(128)->notNull(),
            'district' => $this->string(128)->notNull(),
            'lat' => $this->float()->notNull(),
            'lng' => $this->float()->notNull(),
            ], $tableOptions);

        $this->importCities();
        $this->importBase();
    }

    public function down()
    {
        $this->dropTable('{{%geo__cities}}');
        $this->dropTable('{{%geo__base}}');
    }

    protected function importCities()
    {
        $this->execute("TRUNCATE TABLE {{%geo__cities}}");
        if ($file = file(__DIR__ . '/data/geo/cities.txt')) {
            $pattern = '#(\d+)\s+(.*?)\t+(.*?)\t+(.*?)\t+(.*?)\s+(.*)#';
            foreach ($file as $row) {
                $nRow = iconv('windows-1251', 'utf-8', $row);
                if (preg_match($pattern, $nRow, $out)) {
                    $this->insert('{{%geo__cities}}', [
                        'city_id' => $out[1],
                        'city' => $out[2],
                        'region' => $out[3],
                        'district' => $out[4],
                        'lat' => $out[5],
                        'lng' => $out[6],
                    ]);
                }
            }
            return true;
        }
        return false;
    }

    protected function importBase()
    {
        $this->execute("TRUNCATE TABLE {{%geo__base}}");
        if ($file = file(__DIR__ . '/data/geo/cidr_optim.txt')) {
            $pattern = '#(\d+)\s+(\d+)\s+(\d+\.\d+\.\d+\.\d+)\s+-\s+(\d+\.\d+\.\d+\.\d+)\s+(\w+)\s+(\d+|-)#';
            foreach ($file as $row) {
                $nRow = iconv('windows-1251', 'utf-8', $row);
                if (preg_match($pattern, $nRow, $out)) {
                    $this->insert('{{%geo__base}}', [
                        'long_ip1' => $out[1],
                        'long_ip2' => $out[2],
                        'ip1' => $out[3],
                        'ip2' => $out[4],
                        'country' => $out[5],
                        'city_id' => $out[6],
                    ]);
                }
            }
            return true;
        }
        return false;
    }
}
