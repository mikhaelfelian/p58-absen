<?php
/**
Helper format
https://jagowebdev.com
*/

function format_ribuan($value) {
	if (!$value)
		return 0;
	return number_format($value, 0, ',' , '.');
}