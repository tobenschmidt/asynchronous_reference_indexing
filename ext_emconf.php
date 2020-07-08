<?php
$EM_CONF[$_EXTKEY] = [
  'title' => 'Asynchronous Reference Indexing',
  'description' => 'Delegates reference indexing to a Symfony Console Command (scheduler compatible) to avoid major performance issues on very large setups or large database operations. (TYPO3 10 compatibility by Toben Schmidt)',
  'category' => 'misc',
  'author' => 'Claus Due',
  'author_email' => 'claus@namelesscoder.net',
  'author_company' => '',
  'state' => 'beta',
  'version' => '3.0.0',
  'constraints' => [
    'depends' => [
      'php' => '7.2.0-7.4.99',
      'typo3' => '10.4.0-10.4.99',
    ],
    'conflicts' => [],
    'suggests' => [],
  ],
];
