<?php

use vBuilder\Cli\InputOutput;

include __DIR__ . "/bootstrap.php";

$io = new InputOutput;
// $io->setFlag(InputOutput::NO_COLORS);

$io->writeln("Hello :-)");
$io->writeln();

$io->writeln("Here are some colors:");
$io->writelnf('%{cRed}Var: %{cRedBold}value%{cReset}');
$io->writelnf('%{cGreen}Var: %{cGreenBold}value%{cReset}');
$io->writelnf('%{cYellow}Var: %{cYellowBold}value%{cReset}');
$io->writelnf('%{cBlue}Var: %{cBlueBold}value%{cReset}');
$io->writelnf('%{cMagenta}Var: %{cMagentaBold}value%{cReset}');
$io->writelnf('%{cCyan}Var: %{cCyanBold}value%{cReset}');
$io->writelnf('%{cWhite}Var: %{cWhiteBold}value%{cReset}');
$io->writeln();

// Basic prompt
$answer = $io->ask("Tell me something: ");
$io->writelnf("Your answer: %s", array($answer));

// Secret prompt
$answer = $io->askAndHideAnswer("Tell me something secret: ");
$io->writelnf("Your secret: %s", array($answer));

// Secret prompt
$answer = $io->askForPassword("Tell me your password: ");
$io->writelnf("Your password: %s", array($answer));

$answer = $io->askConfirmation("Do you want anything else?");
$io->writeln($answer ? 'Thats too bad...' : 'Bye bye then.');
$io->writeln();
