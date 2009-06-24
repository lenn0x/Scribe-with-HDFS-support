<?php
//  Copyright (c) 2007-2008 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//
// See accompanying file LICENSE or visit the Scribe site at:
// http://developers.facebook.com/scribe/

$GLOBALS['THRIFT_ROOT'] = '/usr/local/thrift/php/thrift';
$GLOBALS['SCRIBE_ROOT'] = '/usr/local/thrift/php/thrift/packages';
include_once $GLOBALS['SCRIBE_ROOT'].'/scribe.php';
include_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
include_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
include_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocketPool.php';

function simple_test() {
  $messages = array();
  $msg = new LogEntry;
  $msg->category = 'scribe_test';
  $msg->message = "this is a message\n";
  $messages []= $msg;
  $msg2 = new LogEntry;
  $msg2->category = 'scribe_test';
  $msg2->message = "and a binary" . chr(0) . chr(1) . " message\n";
  $messages []= $msg2;
  $msg3 = new LogEntry;
  $msg3->category = 'buckettest';
  $msg3->message = '99' . chr(1) . 'a key-value message with a non-printable delimiter\n';
  $messages []= $msg3;
  $msg4 = new LogEntry;
  $msg4->category = 'buckettest';
  $msg4->message = '99' . chr(1) . 'a different message in the same bucket\n';
  $messages []= $msg4;
  $msg5 = new LogEntry;
  $msg5->category = 'buckettest';
  $msg5->message = '98' . chr(1) . 'a different bucket\n';
  $messages []= $msg5;

  $ret = scribe_Log_test($messages);

  print "Log returned: " . $ret . "\n";
}

function strange_input_test() {
  $messages = array();
  $msg = new LogEntry;
  $msg->category = '%xa\n\ndfsdfjfdsjlkasjlkjerl%slkjasdf%dlkjasdlkjf\\\\\\\\\\\\\adskljasdl;kjsg[pa;lksdkjmdkjfkjfkjkdjfslkkjlaasdfasdfasdfasdfasdfasdflkhjlaksjdlkfjalksjdflkjasdflkjsdaflkjsdflkjadsflkjjsadflkkjsdflkjjsdfalkjfdsakljfsdaljk';
  $msg->message = '%xlasdlkfjalskjlkjasdklg\\\\\\\/////\\\/\\//\\;klf;klds;klfsdaflsk;kl;lk;sfkl;sdf%d<><>><><<>>l;kadsl;kadsl;k;klkl;fsdal;ksdfa;klsaf;lsdfl;kfsdl;ksdflksdaf;lkfds;lkfsd;lksdafl;kf;klsflk;sdf;klsdfka;lskdl;fkls;dfalk;fsdl;ksfadkl;sfdlk;sfadlk;fsld;kasflkad;klfsad;lksdfal;ksfda;lksdaflk;sdfal;kl;sdfakl;sdaf;klsdfa;klsdafk;lsdfakl;sdafkl;sdfak;lasdfkl;sdaflk;sdaflk;sdafkl;sadf;lksdafl;ksdaf;klsdafl;ksdafl;ksdfak;lsdafkl;sdfl;kdsfakl;sdaf;lkdsafk;lsdfkl;sdfakl;fdsa;klfsdk;lasfdk;lfsdakl;sdfak;lfsdakl;sdfakl;sfdak;lsdfaklfdsakl;sdfak;lsfdak;lsfdakl;sfdakl;sdfak;lsdfak;lsdfak;lsdfak;lsfdakl;sdfak;lsdfakl;sdfak;lsdfakl;sdfk;lfds;alkadfsk;lsdfak;ldfsak;lsdfa;klsfdakl;sdfak;lsdfakl;sdfak;lsdfak;lsdf;klsdfa;klsdfak;lsdfak;lfsdakl;sdfakl;fdsak;lsdfak;lsdfakl;sdfak;lsdfak;lsdfa;klsdfa;klsfdk;alsfadkl;sdfakl;sdfkl;fdsakl;sfdal;ksdfak;las;lkfsda;lksdfak;lsdfak;lsdfakl;kfds;lk;l;sdf;klfdsl;ksdfal;ksdl;kds;lksdkl;dsfal;kdsfak;ldsfa;kldfasl;kdfasl;kdafsl;kadfskl;dfsa;kldfsak;ldfsakl;dfask;ldfsak;ldfsal;kdfsakl;dfsak;ldfsak;k;dlfsa;lkadsf;kladsf;lkdfsa;lkdsaf;k;lkdfsakl;dfsal;kdfsa;kldsaf;lkdfsa;kldasf;kldfas;kldsaf;klasdfk;lsadf;klsdafk;ldsaf;lkasdfk;ldfas;kladfs;kldfaskl;dfsa;kldfsakl;dfsalk;dfska;kladsfk;ladfs;kladsfkl;adssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss\n\n\nssssssssssssssssssssssssssssssf';
  $messages []= $msg;
  $msg2 = new LogEntry;
  $msg2->category = 'scribe_test';
  $msg2->message = '\n';
  $messages []= $msg2;

  $ret = scribe_Log_test($messages);

  print "Log returned: " . $ret . "\n";
}

function stress_test($category, $client_name, $rate, $total, $msg_per_call, $avg_size) {

  $send_interval = $msg_per_call/$rate;

  $random = generate_random($avg_size * 2);

  $msgs_since_send = 0;
  $messages = array();
  $last_send_time = microtime(true);

  for($i = 0; $i < $total; ++$i) {

    $entry = new LogEntry;
    $entry->category = $category;
    $entry->message = make_message($client_name, $avg_size, $i, $random);
    $messages []= $entry;

    ++$msgs_since_send;

    if ($msgs_since_send >= $msg_per_call) {

      $msgs_since_send = 0;
      scribe_Log_test($messages);
      $messages = array();
      print '.';

      $now = microtime(true);
      $wait = $last_send_time + $send_interval - $now;
      $last_send_time = $now;
      if ($wait > 0) {
        print "going to sleep $wait ms\n";
        usleep($wait * 1000000);
      } else {
        print "backed up $wait ms\n";
      }
    }
  }
}

function many_connections_test($category, $client_name, $num_connections, $rate, $total, $msg_per_call, $avg_size) {

  if ($num_connections < 1) {
    print("you can't run the test with $num_connections connections\n");
  }

  if ($rate % $num_connections != 0 ||
      $total % $num_connections != 0 ||
      ($total/$num_connections) % $msg_per_call != 0) {
    print("Arguments don't divide evenly, so number of messages won't be accurate\n");
  }

  $rate_per_conn = $rate/$num_connections;
  $send_interval = $msg_per_call/$rate_per_conn;

  // open all the connections. This bypasses the normal client library because
  // we want to open a lot of connections simultaneously, which is something
  // a real client would never need to do.
  //
  $server_ips = array('localhost');
  $port = 1463;
  $socks = array();
  $trans = array();
  $prots = array();
  $scribes = array();
  $opened = array();

  try {
    for ($i = 0; $i < $num_connections; ++$i) {
      $socks[$i] = new TSocketPool($server_ips, $port);
      $socks[$i]->setDebug(1);
      $socks[$i]->setSendTimeout(250);
      $socks[$i]->setRecvTimeout(250);
      $socks[$i]->setNumRetries(1);
      $socks[$i]->setRetryInterval(30);
      $socks[$i]->setRandomize(true);
      $socks[$i]->setMaxConsecutiveFailures(2);
      $socks[$i]->setAlwaysTryLast(false);
      $trans[$i] = new TFramedTransport($socks[$i], 1024, 1024);
      $prots[$i] = new TBinaryProtocol($trans[$i]);
      $scribes[$i] = new scribeClient($prots[$i]);
      $trans[$i]->open();
      $opened[$i] = true;
    }
  } catch (Exception $x) {
    print "exception opening connection $i";
    // Bummer too bad so sad
    return;
  }


  // Send the messages, a few from each connection every loop
  //
  $random = generate_random($avg_size * 2);
  $last_send_time = microtime(true);

  $i = 0;
  while ($i < $total) {

    $messages = array();
    for ($conn = 0; $conn < $num_connections; ++$conn) {
      for ($j = 0; $j < $msg_per_call; ++$j) {

        $entry = new LogEntry;
        $entry->category = $category;
        $entry->message = make_message($client_name, $avg_size, $i, $random);
        $messages []= $entry;
        ++$i;
      }
     $result = $scribes[$conn]->Log($messages);

     if ($result <> OK) {
       print "Warning: Log returned $result \n";
     }

     $messages = array();
    }
    $now = microtime(true);
    $wait = $last_send_time + $send_interval - $now;
    $last_send_time = $now;
    if ($wait > 0) {
      print "going to sleep $wait ms\n";
      usleep($wait * 1000000);
    } else {
      print "backed up $wait ms\n";
    }
  }

  // Close the connections
  //
  for ($i = 0; $i < $num_connections; ++$i) {
    if ($opened[$i]) {
      try {
        $trans[$i]->close();
      } catch (Exception $x) {
        print "exception closing connection $i";
        // Ignore close errors
      }
    }
  }
}

function generate_random($size) {
  $random = 'qwertyiopui%sfmsg;lmad;lkh[pwermflvps/w]slkbb;k,mtjasdlkjfaslkjdfflkjasdfkljaslkdjgfacebookqweetewatab';
  while ($random_len = strlen($random) < $size) {
    $random .= $random;
  }
  return $random;
}

function make_message($client_name, $avg_size, $sequence, $random) {

  $padding_size = $avg_size - strlen($client_name) - 10;
  if ($padding_size > 0) {
    // TODO: we could make this fancier with pseudo-random sizes
    $padding_size = $sequence % ($padding_size * 2);
  } else {
    $padding_size = 0;
  }

  $message = $client_name . '-' . $sequence;
  if ($padding_size) {
    $message .= '-' . $padding_size . '-';
    $message .= substr($random, 0, $padding_size);
  }

  $message .= "\n";
  return $message;
}

function scribe_Log_test($messages) {

  if (!isset($GLOBALS['SCRIBE']['SCRIBE_CLIENT_TEST'])) {
    try {
      // Set up the socket connections
      $scribe_servers = array('localhost');
      $scribe_ports = array(1463);
      print "creating socket pool\n";
      $sock = new TSocketPool($scribe_servers, $scribe_ports);
      $sock->setDebug(0);
      $sock->setSendTimeout(100);
      $sock->setRecvTimeout(250);
      $sock->setNumRetries(1);
      $sock->setRandomize(false);
      $sock->setAlwaysTryLast(true);
      $trans = new TFramedTransport($sock);
      $prot = new TBinaryProtocol($trans);

      // Create the client
      print "creating scribe client\n";
      $scribe_client = new scribeClient($prot);

      // Open the transport (we rely on PHP to close it at script termination)
      print "opening transport\n";
      $trans->open();

      // save it for future calls
      $GLOBALS['SCRIBE']['SCRIBE_CLIENT_TEST'] = $scribe_client;

    } catch (Exception $x) {
      print "Unable to create global scribe client, received exception: $x \n";
      return;
    }
  } else {
    print "using existing scribe client\n";
    $scribe_client = $GLOBALS['SCRIBE']['SCRIBE_CLIENT_TEST'];
  }

  try {
    print "sending messages\n";
    $result = $scribe_client->Log($messages);

    if ($result <> OK) {
      print "Warning: Log returned $result \n";
    }

    return $result;
  } catch (Exception $x) {
    print "Scribe client failed logging " . count($messages) . " messages with exception: $x \n";
  }
}

function super_stress_test($categories, $client_name, $rate, $total, 
                           $msg_per_call, $avg_size) {
  $parallel = count($categories);

  // Fork a new process for every category
  for($i = 0; $i < $parallel; ++$i) {
    $pid = pcntl_fork();

    if($pid == -1) {
      print "Error: Could not fork\n";
      break;
    }
    else if($pid == 0) {
      // In child process
      print "Sending messages for category $categories[$i]...\n";
      stress_test($categories[$i], $client_name, $rate, $total,
                  $msg_per_call, $avg_size);
      print "Done sending messages for category $categories[$i].\n";
      break;
    }
  
  }

}

?>
