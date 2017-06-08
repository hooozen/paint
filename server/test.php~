<?php
function decode($buffer) {
	echo $buffer."\n";
	$len = $masks = $data = $decoded = null;
	$len = ord($buffer[1]) & 127;

	if ($len == 126) {
		$masks = substr($buffer, 4, 4);
		$data = substr($buffer, 8);
	} else if ($len == 127) {
		$masks = substr($buffer, 10, 4);
		$data = substr($buffer, 14);
	} else {
		$masks = substr($buffer, 2, 4);
		$data = substr($buffer, 6);
	}
	for($index = 0; $index < strlen($data); $index++) {
		$decoded .= $data[$index] ^ $masks[$index % 4];
	}
	return $decoded;
}
$buffer = "819a0aca4a7e71e83e077aaf68443ae6680628f0794832e6680728f07b493fb7";
$data = "";
for($i=0; $i<strlen($buffer); $i=$i+2) {
	$asc = (int)$buffer[$i] * 16 + (int)$buffer[$i+1];
	echo $asc."--";
	$data .= chr($asc);
	echo chr((int)$buffer[$i] * 16 + (int)$buffer[$i+1])." ";
}
echo "\n".$data;
echo "\n".decode(ord($data))."\n";
echo "\n".chr(48);
