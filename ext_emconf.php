<?php
$EM_CONF[$_EXTKEY] = [
  'title' => 'Asynchronous Reference Indexing',
  'description' => 'Delegates reference indexing to a command controller (scheduler compatible) to avoid major performance issues on very large setups or large database operations.',
  'category' => 'misc',
  'author' => 'Claus Due',
  'author_email' => 'claus@namelesscoder.net',
  'author_company' => '',
  'state' => 'beta',
  'version' => '2.1.0',
  'constraints' => [
    'depends' => [
      'php' => '7.2.0-7.4.99',
      'typo3' => '10.4.0-10.4.99',
    ],
    'conflicts' => [],
    'suggests' => [],
  ],
];
