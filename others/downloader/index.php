<?php
/* Models */
class Counter {
	var $data;
	var $fileName = './counts.inc.php';
	
	/* Singleton */
	function getMe() {
		static $me = null;
		if (null == $me) {
			$me = new Counter();
		}
		return $me;
	}
	
	/* Constructor */
	function Counter() {
		$this->load();
	}
	
	function load() {
		include ($this->fileName);
	}

	function save() {
		$file = fopen($this->fileName, 'w');
		fwrite($file, "<?php\n\n");
		foreach($this->data as $name => $counts) {
			fwrite($file, "\t\$this->data['" . (string)$name . "'] = " . (int)$counts . ";\n")
		}
		fwrite($file, "?>");
	}
	
	function increase($name) {
		if (!isset($this->data[$name])) {
			$this->data[$name] = 0;
		}
		$this->data[$name]++;
		
		return $this->data[$name];
	}
}

class File {
	var $Counter;
	var $filesDir = './files';
	
	function File() {
		$this->Counter = Counter::getMe();
		$this->filesDir = realpath($this->filesDir) . DIRECTORY_SEPARATOR;
	}
	
	function get($fileName) {
		$fileName = preg_replace('/[^-_a-z0-9.]/i', '', $fileName);
		
		$filePath = $this->nameToPath($fileName);
		if (file_exists($filePath)) {
			$this->Counter->increase($fileName);
			$this->send($filePath);
		} else {
			header('HTTP/1.1 404 File is not exists');
		}
	}
	
	function nameToPath($fileName) {
		$filePath = realpath($this->filesDir . $fileName);
		return $filePath;
	}
	
	function send($filePath) {
		readfile($this->nameToPath($filePath));
	}
	
	function findAll() {
		// Потом реализую
	}
}

/* Controller */
$fileName = isset($_GET['file']) ? $_GET['file'] : false;
$File = new File();
if ($fileName) { // Запросили файло
	$File->get($fileName); // Отправили файло
	die(); // И сдохли 
} else { // Нужен список файлов
	$files = $File->findAll()
	
	// Здесь по-идее всё.
}


/* View */

/*
А тут будешь выводить список файла, както так:
*/

/*
?>
<html>
	<head>
		<title>Список файла</title>
	</head>
	<body>
		<h1>Майо файло</h1>
		<ul>
			<?php foreach($files as $fileName => $downloads): ?>
			<li>
				<a href="<?php echo $fileName; ?>"><?php echo $fileName; ?></a>

<?php echo $fileName; ?>, скачано <?php echo $downloads; ?> раз</li>
			<?php endforeach; ?>
		</ul>
	</body>
</html>
*/