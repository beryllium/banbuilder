<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use PHPUnit\Framework\TestCase;
use Snipe\BanBuilder\CensorWords;

class CensorTest extends TestCase
{

    public function testSetDictionary()
    {
        $censor = new CensorWords;
        $censor->setDictionary('fr');
        $this->assertNotEmpty($censor->badwords);
    }

    public function testAddDictionary()
    {
        $censor = new CensorWords();
        $censor->addDictionary('fr');

        $this->assertNotEmpty($censor->badwords);

        $string1 = $censor->censorString('fuck');
        $this->assertEquals('****', $string1['clean']);

        $string2 = $censor->censorString('nique');
        $this->assertEquals('*****', $string2['clean']);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testInvalidDictionaryException()
    {
        $censor = new CensorWords;
        $this->assertNotEmpty($censor->setDictionary('poopfaced-blahblah-this-file-isnt-real'));
    }

    public function testLoadMultipleDictionaries()
    {
        $censor = new CensorWords();
        $censor->setDictionary(array(
            'en-us',
            'en-uk',
            'fr'
        ));
        $this->assertContains('punani', $censor->badwords);
        $this->assertContains('doggystyle', $censor->badwords);
        $this->assertContains('salaud', $censor->badwords);
    }

    public function testFuckeryClean()
    {
        $censor = new CensorWords;
        $string = $censor->censorString('fuck');
        $this->assertEquals('****', $string['clean']);

    }

    public function testWordFuckeryClean()
    {
        $censor = new CensorWords;
        $string = $censor->censorString('abc fuck xyz', true);
        $this->assertEquals('abc **** xyz', $string['clean']);

        $string2 = $censor->censorString('Hello World', true);
        $this->assertEquals('Hello World', $string2['clean']);

        $string3 = $censor->censorString('fuck...', true);
        $this->assertEquals('****...', $string3['clean']);
    }

    public function testFuckeryOrig()
    {
        $censor = new CensorWords;
        $censor->setDictionary('en-us');
        $string = $censor->censorString('fuck');
        $this->assertEquals('fuck', $string['orig']);

    }

    public function testFuckeryCustomReplace()
    {
        $censor = new CensorWords;
        $censor->setReplaceChar('X');
        $string = $censor->censorString('fuck');
        $this->assertEquals('XXXX', $string['clean']);

    }

    public function testFuckeryCustomReplaceException()
    {
        $censor = new CensorWords;
        $censor->setReplaceChar('x');
        $string = $censor->censorString('fuck');
        $this->assertNotEquals('****', $string['clean']);

    }


    public function testSameCensorObj()
    {
        $censor = new CensorWords;
        $string = $censor->censorString('fuck');
        $this->assertEquals('****', $string['clean']);
        $string2 = $censor->censorString('fuck');
        $this->assertEquals('****', $string2['clean']);

    }

  public function testWhiteListCensorObj()
  {
    $censor = new CensorWords;
    $censor->addWhileList([
        'fuck',
        'ass',
        'Mass',
    ]);

    $string = $censor->censorString('fuck dumb ass bitch FUCK Mass');
    $this->assertEquals('fuck dumb ass ***** **** Mass', $string['clean']);
  }

    /**
     * @dataProvider sevenDirtyWordsProvider
     */
  public function testSevenWords($clean, $matched, $orig)
  {
      $censor   = new CensorWords;
      $expected = [
          'orig'    => $orig,
          'clean'   => $clean,
          'matched' => $matched,
      ];
      $actual   = $censor->censorString($orig);
      $this->assertSame($expected, $actual);
  }

  public function sevenDirtyWordsProvider()
  {
      // identify datasets by a recognizable key
      $words = [
          'shit'         => ['clean' => '****', 'matched' => ['shit']],
          'piss'         => ['clean' => '****', 'matched' => ['piss']],
          'fuck'         => ['clean' => '****', 'matched' => ['fuck']],
          'cunt'         => ['clean' => '****', 'matched' => ['cunt']],
          'cocksucker'   => ['clean' => '****sucker', 'matched' => ['cock']],
          'motherfucker' => ['clean' => 'mother****er', 'matched' => ['fuck']],     // "He says motherfucker is a duplication of the word fuck, technically, because fuck is the root form, motherfucker being derivative; therefore, it constitutes duplication. And I said, 'Hey, motherfucker, how did you get my phone number, anyway?'"
          'tits'         => ['clean' => 'tits', 'matched' => []],     // ("New Nabisco Tits! ...corn tits, cheese tits, tater tits!")
      ];

      // we already had to write each one twice, let's automate away the third
      return array_map(
          function ($data, $key) {$data['orig'] = $key; return $data;},
          $words,
          array_keys($words)
      );
  }
}
