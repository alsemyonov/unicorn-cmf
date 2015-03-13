# Введение #

Модель в архитектуре MVC является прослойкой для работы с данными. Реализация Model в Unicorn представлена несколькими классами:
  * Model - ключевой абстрактный класс, реализующий базовые методы
    * DbModel - ORM прослойка к БД
    * YamlModel - YAML
  * Datasource - Класс, обеспечивающий Модель данными
    * PdoDatasource - Реализация через PDO
    * MysqlDatasource - Реализация для MySQL
  * Behavior - Поведение. Позволяет добавлять к любым моделям типовую функциональность
    * ACLBehavior - Реализация прав доступа к моделям
    * TreeBehavior - Реализация деревьев


# Model #

Модель содержит данные, манипулирует ими, позволяет загружать и схоранять их, также проверять, были ли совершены изменения

```
interface Model {
  // Методы класса
  function __construct($id = null); // Считывает из БД по ПК, либо создаёт "чистый" объект
  static public function create(); // Создаёт "чистый" объект
  static public function read($id); // Читает из БД
  static public function update($data, $id);
  static public function delete($id);

  // Методы объекта
  public function reload();
  public function save();
  public function isModified();
}
```

# DbModel #

Реализация ORM.

```
interface DbModel {
  static funtion findBy__();
}
```