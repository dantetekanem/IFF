IFF
===

IFF - Imagine Form For (forms made easy for Fuel PHP! Based on rails simple_form_for)

Inspirated by the Rails Gem: SimpleForm (created by Plataformatec), the proposal of IFF is to generate your forms, auto-fill the values and contents with your model data, work with the form validation observer and the jquery.validation plugin, and the most important goal: never limit yourself.
Yes, I hate when a plugin or a class, instead of given me the tools to make my work faster, it limit me on other ways that are really bothering.


HOW TO USE
===

Open your bootstra.php file (located at fuel/app/), and add the IFF to autoloader, like this:
```php
Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
	'IFF' => APPPATH.'classes/iff.php',
));
```

Now put the iff.php file on your classes folder (just like the git structure), and use it!


Here's a simple demo:

```php
<? $f = new IFF($user, array('method' => 'post', 'action' => 'users/create')) ?>

	<p>
		<?= $f->label('name', 'Name:') ?>
		<?= $f->input('name') ?>
	</p>

	<p>
		<?= $f->label('email', 'E-mail:') ?>
		<?= $f->input('email') ?>
	</p>

	<p>
		<?= $f->label('gender', 'Gender:') ?>
		<?= $f->select('gender', array('male' => 'Male', 'female' => 'Female'), array('has_blank' => 'Select')) ?>
	</p>

	<p>
		<?= $f->label('avatar', 'Avatar:') ?>
		<?= $f->file('avatar') ?>
	</p>

	<p>
		<?= $f->submit('Submit') ?>
	</p>

<?= $f->end() ?>
```