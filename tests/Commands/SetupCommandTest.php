<?php

namespace AcquiaCli\Tests\Commands;

use AcquiaCli\Tests\AcquiaCliTestCase;

class SetupCommandTest extends AcquiaCliTestCase
{
    public function testSetupConfigViewDefault(): void
    {
        $command = ['setup:config:view'];
        $defaultConfiguration = <<< DEFAULT
                                             
             Default configuration           
                                             
acquia:
  key: 'd0697bfc-7f56-4942-9205-b5686bf5b3f5'
  secret: 'D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE='
extraconfig:
  timezone: 'Australia/Sydney'
  format: 'Y-m-d H:i:s'
  taskwait: 5
  timeout: 300

                                             
             Running configuration           
                                             
acquia:
    key: d0697bfc-7f56-4942-9205-b5686bf5b3f5
    secret: D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE=
extraconfig:
    timezone: Australia/Sydney
    format: 'Y-m-d H:i:s'
    taskwait: 5
    timeout: 300


DEFAULT;

        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($defaultConfiguration, $actualResponse);
    }

    public function testSetupConfigViewOverwritten(): void
    {
        $command = ['setup:config:view'];
        $overwrittenConfiguration = <<< OVERWRITTEN
                                             
             Default configuration           
                                             
acquia:
  key: 'd0697bfc-7f56-4942-9205-b5686bf5b3f5'
  secret: 'D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE='
extraconfig:
  timezone: 'Australia/Sydney'
  format: 'Y-m-d H:i:s'
  taskwait: 5
  timeout: 300

                                             
           Environment configuration         
                                             
extraconfig:
    timezone: Australia/Melbourne
    format: U

                                             
             Running configuration           
                                             
acquia:
    key: d0697bfc-7f56-4942-9205-b5686bf5b3f5
    secret: D5UfO/4FfNBWn4+0cUwpLOoFzfP7Qqib4AoY+wYGsKE=
extraconfig:
    timezone: Australia/Melbourne
    format: U
    taskwait: 5
    timeout: 300


OVERWRITTEN;

        putenv('ACQUIACLI_TIMEZONE=Australia/Melbourne');
        putenv('ACQUIACLI_FORMAT=U');

        $actualResponse = $this->execute($command);
        $this->assertSameWithoutLE($overwrittenConfiguration, $actualResponse);

        putenv('ACQUIACLI_TIMEZONE=');
        putenv('ACQUIACLI_FORMAT=');
    }
}
