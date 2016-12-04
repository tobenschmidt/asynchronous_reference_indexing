<?php
$EM_CONF[$_EXTKEY] = [
  'title' => 'Asynchronous Reference Indexing',
  'description' => 'Delegates reference indexing to a command controller (scheduler compatible) to avoid major performance issues on very large setups or large database operations.',
  'category' => 'misc',
  'author' => 'Claus Due',
  'author_email' => 'claus@namelesscoder.net',
  'author_company' => '',
  'shy' => '',
  'dependencies' => '',
  'conflicts' => '',
  'priority' => '',
  'module' => '',
  'state' => 'beta',
  'internal' => '',
  'uploadfolder' => 0,
  'createDirs' => '',
  'modify_tables' => '',
  'clearCacheOnLoad' => 0,
  'lockType' => '',
  'version' => '1.0.5',
  'constraints' => [
    'depends' => [
      'php' => '5.6.0-7.0.99',
      'typo3' => '7.6.0-8.4.99',
    ],
    'conflicts' => [],
    'suggests' => [],
  ],
  'suggests' => [],
  '_md5_values_when_last_written' => '',
];