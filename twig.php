<?php
// Load Twig globals
$app->view(new \Slim\Extras\Views\Twig());
$twig = $app->view()->getEnvironment();

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset'           => 'utf-8',
    'cache'             => 'cache/templates',
    'auto_reload'       => true,
    'strict_variables'  => false,
    'autoescape'        => true
);

\Slim\Extras\Views\Twig::$twigExtensions = array(
);

// Twig globals
$twig->addGlobal("siteurl", $baseAddr);
//$twig->addGlobal("fullsiteurl", "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
$twig->addGlobal("image_character", "https://image.eveonline.com/Character/");
$twig->addGlobal("image_corporation", "https://image.eveonline.com/Corporation/");
$twig->addGlobal("image_alliance", "https://image.eveonline.com/Alliance/");
$twig->addGlobal("siteName", $siteName);

$twig->addExtension(new UserGlobals());

$twig->addFunction("pageTimer", new Twig_Function_Function("Util::pageTimer"));
$twig->addFunction("queryCount", new Twig_Function_Function("Db::getQueryCount"));
$twig->addFunction("isActive", new Twig_Function_Function("Util::isActive"));

$twig->addGlobal("sessionusername", @$_SESSION['character_id']);

$chars = [];
if (isset($_SESSION['character_id']) && $_SESSION['character_id'] > 0) {
	$chars = findChars($_SESSION['character_id']);
	$validChars = $chars;
	$chars = Db::query("select distinct i.characterID, characterName, trainingTypeID typeID, trainingToLevel, trainingEndTime, balance, i.cachedUntil, queueFinishes, subFlag from skq_character_info i left join skq_character_training t on (i.characterID = t.characterID) where i.characterID in (" . implode(",", $chars) . ") order by skillPoints desc");
	$twig->addGlobal("characters", $chars);
}

function findChars($charID, &$chars = []) {
	if (sizeof($chars) == 0) $chars = [$charID];
	foreach ($chars as $char) {
		$result = Db::query("select char2 c from skq_character_associations where char1 = :char", [':char' => $char]);
		foreach ($result as $row) {
			$nextChar = (int) $row['c'];
			if (!in_array($nextChar, $chars)) {
				$chars[] = $nextChar;
				findChars($nextChar, $chars);
			}
		}
	}
	foreach ($chars as $char) {
		$result = Db::query("select char1 c from skq_character_associations where char2 = :char", [':char' => $char]);
		foreach ($result as $row) {
			$nextChar = (int) $row['c'];
			if (!in_array($nextChar, $chars)) {
				$chars[] = $nextChar;
				findChars($nextChar, $chars);
			}
		}
	}
	return array_unique($chars);
}
