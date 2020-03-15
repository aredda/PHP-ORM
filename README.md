<h2>A simple PHP mapping library</h2>
<p>By utilizing document comments, I managed to build a primitive annotation system, which allowed me to give extra information about properties,
so I could construct valid and precise SQL queries from the provided models.
</p>
<p>However, it is still under development.</p>

<h2>Demonstration</h2>
<p><b>In this situation, we will create our database from the code we will write, it is known as "code-first".</b></p>
<p>Let us suppose that we have two entities:
<p>• <b>Country</b> (<b>id</b>, name)</p>
<p>• <b>Band</b> (<b>id</b>, name, genre, #country)</p>

<h5>1. We will provide our models in an object-oriented style like that:</h5>
<img src='https://i.imgur.com/286E3zY.jpg' />
<h5>1. Next, we need a context/container/setup/communicator with the SQL Server, in order to build a context we need to create a class that extends <code>Database</code> as shown below:</h5>
<img src='https://i.imgur.com/Wdhcd4g.jpg' />
<ul>  
  <li><code>Database</code> is an abstract class that holds all of the required logic in order to act like a Context.</li>
  <li><code>Database::map(args)</code> is a method that takes the Models we want to map.</li>
  <li><code>Database::setup()</code> is an abstract method that should be defined, it should contain the <code>Database::map(args)</code> instruction,
  to determine which models to map.</li>
  <li><code>Database::is_created()</code> checks if there's a database with the same name as the Context Class <b>(In our case it is <code>TestDatabase</code>)</b></li>
  <li><code>Database::create()</code> generates an SQL script from the provided models, then attempts to create the database, returns false if the operation fails.</li>
  <li><code>Database::refresh($lazyMode = false)</code> retrieves data from SQL Server, then it distributes the data to the containers of each provided model,<br>
  <code>$lazyMode = false</code> tells the Context to bring data excluding <code>hasMany</code> relations.</li>
</ul>

<h5>3. To use the context, we should instantiate an object of our <code>TestDatabase</code> class:</h5>
<p>• <code>Database::__construct($connection)</code> takes a connection object as a parameter.
<br>• <code>Database::setup()</code> is invoked within the constructor.</p>
<img src='https://i.imgur.com/CsT65No.jpg'/>

<h5>4. Go to your phpMyAdmin panel, to confirm the creation of your database:</h5>
<img src='https://i.imgur.com/PLchVHH.jpg' />
<h4>Country model:</h4>
<img src='https://i.imgur.com/tqLWy5I.jpg' />
<h4>Band model:</h4>
<img src='https://i.imgur.com/l8ckzOS.jpg' />

<h4>That's it, you're ready to go.</h4>
<hr>
<i>I have implemented this simple ORM in one of my other projects, if you're interested in other examples, <a href='https://github.com/IbraheemAredda/ArDoros/tree/master/src/models'>check this</a>.</i>
<br><i><b>More details and documentation will be available as soon as I manage to improve it.</b></i>
