<?php

require_once __DIR__."/system.php";



if(all("login")=="achas911msczxl-AYTACH-336699CX"){
  // https://example.com/system/organizer.php?login=achas911msczxl-AYTACH-336699CX
  login("WOLF","organizer-cookie");
  redirect("?");
}

if(all("logout")){
  logout("organizer-cookie");
  redirect("?");
}


require_once( __DIR__."/../wolf/index.php" );

/*
require_once( __DIR__."/../wolf/index.php" );
$isLogged = FileManager::isLogged();
if($isLogged==0){
  die( '<body style="display:flex;background:#DDD;align-items: center;justify-content: center;"><h1 style="font-family:monospace;text-align:center;">No Access</h1></body>' );
}
*/

if(id("organizer-cookie")==false){
  die( '<body style="display:flex;background:#DDD;align-items: center;justify-content: center;"><h1 style="font-family:monospace;text-align:center;">No Access</h1></body>' );
}


if(post("remove")){
  $db->remove(post("table"),post("remove"));
  echo "Removed";
  exit();
}


if(all("get")){
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode( $db->get(all("table"),all("get")) );
  exit();
}


$tables = [


];

if(post("update")){
  $new_id   = post("id");    // new
  $id       = post("_id",$new_id);   // old
  $table    = trim(post("update"));
  if(array_key_exists($table,$tables)){
    $parameters = $tables[$table];
  }else{
    $parameters = $db->columns($table);
  }
  $datas = alls($parameters);
  foreach($parameters as $param){ if(all($param,false)===false){ unset($datas[$param]); } }
  $datas["id"] = $id;

  if(isset($_FILES["image"])){
    $tmp = explode('.', $_FILES['image']['name']);
    $ext = end($tmp);
    $targetFile = "/uploads/" . md5(time()) . "." . $ext;
    move_uploaded_file($_FILES["image"]["tmp_name"], __DIR__."/../".$targetFile);
    $datas["image"] = $targetFile;
  }

  $count = $db->set($table,$datas) ? 1 : 0;
  if( $id!=$new_id ){
    $count += $db->sql("UPDATE $table SET id=? WHERE id=?",[$new_id,$id]);
  }
  $db->save();
  if(all("json")){
    json("$count Item Updated!");
  }
  echo "$count Item Updated!";
  exit();
}



if(post("insert")){
  
  $table      = trim(post("insert"));
  if(array_key_exists($table,$tables)){
    $parameters = $tables[$table];
  }else{
    $parameters = $db->columns($table);
  }
  $datas      = alls($parameters,"trim");
  foreach($parameters as $param){ if(all($param,false)===false){ unset($datas[$param]); } }


  if( isset($_FILES["image"]) ){
    $ext = end( explode(".", $_FILES['uploaded_file']['name']) );
    $targetFile = "/uploads/" . md5(time()) . ".png" ;
    move_uploaded_file($_FILES["image"]["tmp_name"], __DIR__."/../".$targetFile);
    $datas["image"] = $targetFile;
  }

  $id=$db->add($table,$datas);
  if($db->error){
    json($db->error);
  }
  $datas["id"] = $db->sql("SELECT id FROM ".addslashes($table)." ORDER BY id DESC LIMIT 1")[0]["id"];
  $db->save();
  if(all("json")=="true") {
    @$datas["user.name"] = $user["name"];
    json("Item inserted.");
    //json($datas);
  }
  redirect("?");
}


if(all("action")=="import"){
  if ($_FILES['file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $data = file_get_contents($_FILES['file']['tmp_name']); 
    $datas = json_decode($data,true);
    $count = 0;
    foreach($datas as $table=>$rows){
      foreach($rows as $row){
        $count += $db->set($table,$row);
      }
    }
    message($count . " adet veri güncellendi yada eklendi!");
  }
}

if(all("action")=="export"){
  $response=[];
  foreach($tables as $table=>$parameters){
    $response[$table]=$db->all($table);
  }
  json($response);
}


if(all("message-read")){
  $message = $db->get("contact",all("message-read"));
  $db->set("contact",["id"=>all("message-read"),"seen"=>1]);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($message);
  exit();
}






if(all("action")=="show"){
  // sql
  $columns = explode(",",all("columns"));
  array_unshift($columns,"id");

  $table   = trim( addslashes( all("table") ) );
  $joins = [];
  $columns_sql = [];
  $id_columns = [];
  $selects = []; // user=>name
  foreach($columns as $column){
    $column = trim($column);
    if( strpos($column,".")===false ){
      $columns_sql[] = "`t0`.`{$column}` as '{$column}'";
      $id_columns[] = "`t0`.`{$column}` as '{$column}'";
    }else{
      $t = trim(explode(".",$column)[0]);
      $c = trim(explode(".",$column)[1]);

      $_id_sql   = "`{$t}`.`id` as '{$t}.id' ";
      $_text_sql = "`{$t}`.`{$c}` as '{$column}'";
    
      $joins[] = $t;
      $columns_sql[] = $_text_sql;
      
      
      if( array_search($t, array_column($selects, '0'))!==false ){
        $id_columns[] = $_text_sql;
      }else{
        $id_columns[] = $_id_sql.",".$_text_sql;
      }
      $selects[] =[$t,$c];
    }
  }
  $readable_sql = "SELECT ".join(',',$columns_sql)." FROM `{$table}` t0";
  foreach($joins as $join){
    $readable_sql .= " LEFT JOIN `$join` ON `{$join}`.id = `t0`.{$join}_id ";
  }

  $id_sql = "SELECT ".join(',',$id_columns)." FROM `{$table}` `t0`";
  foreach(array_unique($joins) as $join){
    $id_sql .= " LEFT JOIN `$join` ON `{$join}`.id = `t0`.{$join}_id ";
  }
  $id_sql .=  "ORDER BY ".addslashes(all("order"))." LIMIT " . intval(all("count",10)) . " OFFSET " . intval(all("start",0));

  
  //$main = ;
  $datas = [];
  $datas[ $table ] = $db->sql($id_sql);
  $datas["_count"] = $db->sql("SELECT COUNT(*) as c FROM `{$table}`")[0]["c"];

  $_selects = array();
  foreach ($selects as $item) {
      $key = $item[0];$value = $item[1];
      if (!isset($_selects[$key])) {
          $_selects[$key] = array();
      }   
      $_selects[$key][] = $value;
  }

  foreach($_selects as $table=>$columns){
    $datas[$table] = $db->sql("SELECT `{$table}`.id , "."`{$table}`.`" . implode("`, `{$table}`.`", $columns). "` FROM `{$table}` ORDER BY id");
  }


  json( $datas );
}

function file_response($string_content, $file_name) {
  // Set headers for file download
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . $file_name . '"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . strlen($string_content));
  
  // Send the content
  echo $string_content;
  
  // Terminate script execution
  exit;
}

if(all("download-csv")){
  file_response( csv_stringify ( $db->sql("SELECT * FROM ".all("download-csv"). " ORDER BY id ") ) ,"academic_onboarding.csv" );
}

if(all("download-json")){
  file_response( json_encode ( $db->sql("SELECT * FROM ".all("download-json"). " ORDER BY id ") ) ,"academic_onboarding.json" );
}



if(all("sql")){
  // HERE MUST BE SELECT
  $sql = trim( trim(all("sql")) , "\n" );
  if( strpos(strtolower(str_replace(["(",")"," "], "", $sql)), "select") === 0 ){
    json( $db->sql(post("sql")) );
  }else{
    json("sql must be start with SELECT");
  }
}

?>
<head>
  <link rel="icon" href="asenax-logo-red.svg">
</head>
<script src="../master/?page=file-manager-library"></script>

<style>
body{
  user-select:text;
}
/*
body.theme-dark {
  --front: #FFF;
  --back: #181818;
  --primary: #FFEB3B;
  --primary-text:#000;
  --light: #FFF455;
}
body.theme-dark [table] button{
  color:white!important;
}
*/
body menu a{
  /*border-radius:0!important;*/
}

/*
::-webkit-scrollbar { width: 12px; }

::-webkit-scrollbar-track { background: #8888; }
 
::-webkit-scrollbar-thumb { background: #888; }

::-webkit-scrollbar-thumb:hover { background: #555; }
body.theme-dark ::-webkit-scrollbar-thumb:hover { background: #DDD; }
*/

</style>



<script src="https://apps.asenax.com/documenter/documenter.js?v=3"></script>
<!-- <script src="//hasandelibas.github.io/documenter/documenter.js"></script> -->
<meta charset="utf8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<header body-class="show-menu theme-dark tab-system documenter-admin">
  <!-- <a href="../" icon-button documenter-icon-arrow-left></a> -->
 
  <!-- <b onclick="window.open(&quot;https://asenax.com&quot;,&quot;_blank&quot;)" style="color:#F03;cursor:pointer;">Asenax</b> -->
  <div title="" onclick='window.location = window.location.href.split("#")[0];' style='cursor:pointer;font-weight:800;opacity:.8'>
    📚 Organizer
  </div>



  <div class="space"></div>
  
  <!-- <button data-refresh style='margin:0' tooltip-bottom="Click to update" onclick="refreshOrganizer()">⟳ Refresh</button> -->
  <script>
    documenter.on("ready",function(){
      $('[href="#Academic-Onboarding-View"]').addEventListener("click",function(){
          $("#documenter-tab-Academic-Onboarding-View documenter-organizer").refresh()
      })
    })
    function refreshOrganizer(){
      $$("documenter-organizer").map(e=>e.refresh() )
    }
  </script>

  <button icon-button documenter-icon-contrast onclick="document.body.classList.toggle('theme-dark');document.body.classList.toggle('theme-light')"></button>

  <a href="?logout=logout" icon-button documenter-icon-power style="color:red;"></a>

  <button icon-button documenter-icon-fullscreen onclick="document.fullscreenElement == null ? document.documentElement.requestFullscreen() : document.exitFullscreen()"></button>



  

</header>

<?php if(FileManager::isLogged()) {  ?>

# 🤖 Developer

<div class="box" style="margin-bottom:1em;">
  <button onclick="documenter.post('?action=export').then(e=>e.text()).then(e=>documenter.download(e, location.host +'-'+ (new Date()).toISOString().split('T')[0] + '-backup.json' ))"> Export </button>
</div>

<form action="?" class="box" flex-x gap enctype="multipart/form-data" method="post" style="display:none">
  <input type="hidden" name="action" value="import"> 
  <input name="file" type="file" accept=".json"> <button>Import</button>
</form>

```
id() = <?= id() ?> 
domain() = <?= domain() ?> 
```

```yaml
tables          : <?php write(join(" , ",$db->tables())) ?>
<?php 
  foreach( $db->tables() as $table ) {
    write($table, ":" , "\n" . join(" , ",$db->columns($table)));
  }
?>
```

```
<?php write($_SERVER) ?>
```

<style>
  [td],[th]{
    max-width:200px;
  }
  [td]>div,[th]>div{
    text-overflow: ellipsis;
  }
</style>


# 🖥 SQL
<div flex-x>
  <textarea id="sql-text" space spellcheck="false" style="height:120px;">SELECT * FROM email_convo INNER JOIN onboarding_form  ON email_convo.email = onboarding_form.email</textarea>
  <button id="sql-button">SELECT</button>
</div>
<div id="sql-result"></div>
<style>
#documenter-tab-🖥-SQL td,#documenter-tab-🖥-SQL th{
  max-height: 32px;
  max-width: 100px;
  overflow: hidden;
  position: relative;
  text-overflow: ellipsis;
  white-space: pre;
}
</style>
<script>
  documenter.on("ready",function(){
    documenter.on("click","#sql-button",function(){
      documenter.post("?",{sql:$("#sql-text").value}).then(e=>e.json()).then(e=>{
        $("#sql-result").innerHTML = ""
        $("#sql-result").appendChild( documenter.table(e)) 
      })
    })
  })
</script>


<?php } ?>

# Example Table
<documenter-organizer
  table-name = "table_name" 
  show       = "id:text:ID,email:text:Email,name:text:Full Name,question:text:Question" 
  create     = "email:text:Email,name:text:Full Name,question:textarea:Question" 
  editing    = "form" 
  style="display:inline-block;min-width:100%;"
  ></documenter-organizer>




<!--- documenter-organizer -->  
<style>
  [table]{
    display: table;
    border-collapse: collapse;
    width:100%;
  }
  [table] [tr]{
    display:table-row;
  }
  [th]{
    font-weight: bold;
    text-align: center;
    word-space:nowrap;
  }
  [table] [tr] [td], [table] [tr] [th] {
    display:table-cell;
    border:2px solid var(--light);
    padding:6px;
    vertical-align: middle;
  }
</style>
<style>
documenter-organizer [td][type=image] img{
  height:2.4em;
  width:100%;
  object-fit:cover;
}
documenter-organizer [td][type=image][editable] img{
  cursor:pointer;
}

</style>
<style>
div[type=image] {
    width: 60px;
}
div[name=id] {
    width: 36px;
}
</style>
  

<script>

documenter.organizer = function(parent){
  let OPTIONS = {
    // string
    tableName : parent.getAttribute("table-name").trim(),
    // list []
    show      : parent.getAttribute("show").split(",").map(e=>e.trim().split(":")[0]).map(e=>e.trim()).filter(e=>e!=""),
    // list []
    create    : (parent.getAttribute("create")||"").split(",").map(e=>e.trim().split(":")[0]).map(e=>e.trim()).filter(e=>e!=""),
    // list []
    edit      : (parent.getAttribute("edit")||"").split(",").map(e=>e.trim().split(":")[0]).map(e=>e.trim()).filter(e=>e!=""),
    // {text:text}
    titles    : {},
    // {text:text}
    types     : {},
    // ["tr", "en", "fr"]
    languages : (parent.getAttribute("languages")||"").split(",").map(e=>e.trim()).map(e=>e.trim()).filter(e=>e!=""),
    // form | inline
    editing   : (parent.getAttribute("editing")||"inline").trim()
  }

  // OPTIONS define
  for( let row of [...parent.getAttribute("show").split(","),...(parent.getAttribute("create")||"").split(","),...(parent.getAttribute("edit")||"").split(",") ] ){
    let column = row.split(":")[0].trim()
    if(row.split(":")[2]){
      let title = row.split(":")[2].trim()
      OPTIONS.titles[column] = title
      let first = column.split(".")[0].trim()
      if(OPTIONS.titles[first]==null) OPTIONS.titles[first] = title
    }
    if(row.split(":")[1]){
      let type = row.split(":")[1].trim()
      if(type=="" && column.includes(".")) type = "select"
      if(type=="") type="text"
      OPTIONS.types[column] = type
    }
    if(row.split(":")[0]){
      let type="text"
      if(column.includes(".")) type = "select"
      if(!OPTIONS.types[column]) OPTIONS.types[column] = type
    }
    if(OPTIONS.titles[column] == null) OPTIONS.titles[column] = column
    // let first = column.split(".")[0].trim()
    // if(OPTIONS.titles[first] == null) OPTIONS.titles[first] = first
  }
  console.log(OPTIONS)
  
  /*
  let tableName = parent.getAttribute("table-name")
  let show      = parent.getAttribute("show").split(",").map(e=>e.trim().split(":")[0])
  let types     = parent.getAttribute("show").split(",").map(e=>e.trim().split(":")[1] || "text")
  show.map((e,i)=>e.includes(".")?types[i]="select":"" )
  let headers   = parent.getAttribute("headers").split(",").map(e=>e.trim())
  
  let editables = parent.getAttribute("edit").split(",").map(e=>e.trim())
  let editing   = parent.getAttribute("editing")
  */


  let header = documenter.render("<div flex-x center gap padding-bottom><button data-create style='margin:0'>+ Create</button> <space></space> Page:  <select data-page style='margin:0'></select> Item:<select data-count style='margin:0'><option>10</option><option>20</option><option>100</option><option>250</option><option>500</option><option>1000</option></select></div>")
  parent.appendChild(header)
  header.querySelector("[data-count]").oninput = header.querySelector("[data-page]").oninput = function(){
    let count = parseInt(header.querySelector("[data-count]").value )
    let page  = parseInt(header.querySelector("[data-page]").value )
    parent.show( (page-1) * count, count, "id desc" )
  }
  header.querySelector("[data-create]").onclick = ()=> parent.edit()

  parent.refresh = function(){
    let count = parseInt(header.querySelector("[data-count]").value )
    let page  = parseInt(header.querySelector("[data-page]").value )
    parent.show( (page-1) * count, count, "id desc" )
  }
  
  let headerLanguageDropdown
  if(OPTIONS.languages.length>0){
    headerLanguageDropdown = documenter.render(`<dropdown name="language" style="min-width: 200px;margin:0;display: flex;width: 200px;" value="${OPTIONS.languages[0]}"></dropdown>`);
    for(let language of OPTIONS.languages){
      let name = documenter.organizer.flags.findIndex(e=>e[0]==language)
      name = name==-1 ? language : documenter.organizer.flags[name][2]
      let flag = documenter.organizer.flags.findIndex(e=>e[0]==language)
      flag = flag==-1 ? language : documenter.organizer.flags[flag][1]
      headerLanguageDropdown.appendChild(documenter.render(`<div value="${language}"> <img src="https://flagcdn.com/w80/${flag}.png" style="aspect-ratio:3/2"> ${name} </div>`))
    }
    documenter.dropdown(headerLanguageDropdown)
    headerLanguageDropdown.oninput = function(){
      Array.from( parent.body.querySelectorAll("[tr]") ).filter(e=>e.getAttribute("tr")!="").map(e=>{
        let id = parseInt( e.getAttribute("tr") )
        Array.from(e.querySelectorAll('[type="text"]')).map(e=>{
          let name = e.getAttribute("name")
          try{
            e.firstElementChild.innerHTML = JSON.parse( parent.datas[OPTIONS.tableName].find(e=>e.id==id)[name] )[ headerLanguageDropdown.value ]
          }finally{}
        })
      })
    }
    header.appendChild(headerLanguageDropdown)
  }


  let body = documenter.render("<div style='background:var(--back);'></div>")
  parent.appendChild(body)
  parent.body = body
  
  // 10 5 id desc
  parent.show = function(start,count,order){
    documenter.post("?action=show",{
      table   : OPTIONS.tableName,
      columns : Array.from(new Set([...OPTIONS.show,...OPTIONS.create,...OPTIONS.edit])).join(","),
      start   : start,
      count   : count,
      order   : order
    }).then(e=>e.json()).then(datas=>{
      parent.datas = datas
      // let select
      let selects = {}
      for(let sel of OPTIONS.show){
        if(sel.includes(".")){
          selects[sel] = documenter.render("<select style='display:none'></select>")
          let _table  = sel.split(".")[0].trim();
          let _column = sel.split(".")[1].trim();
          for(let option of datas[_table]){
            selects[sel].appendChild( documenter.render("<option value='"+option.id+"'>"+option[_column]+"</option>") )
          }
        }
      }

      // table header
      let html = "<div table='"+OPTIONS.tableName+"' class='documenter-editable'>"
      html += "<div tr>"+ OPTIONS.show.map(e=>OPTIONS.titles[e]).map(e=>"<div th>"+e+"</div>").join("") +"<div th>Action</div></div>"

      for(let data of datas[OPTIONS.tableName]){
        html += "<div tr='"+data.id+"'>"+ OPTIONS.show.map((e,i)=>{
          let editable = OPTIONS.edit.includes(e) ? "editable" : ""
          if(OPTIONS.editing!="inline") editable = "";
          let value = e.includes(".") ? "value='"+data[e.split(".")[0]+".id"] + "'" : ""
          return `<div  name='${e}' type='${OPTIONS.types[e]}' ${editable} ${value}   td>${data[e]}</div>`
        }).join("")
        html += `<div td actions>
            <button button-remove onclick='documenter.organizer.remove(this)' style="background:red;font-size:.8em;margin-right:0">𐄂 Delete</button>`
        if(OPTIONS.editing=="inline"){
          html += `<button button-update onclick='documenter.organizer.update(this)' style="background:#4CCD68;font-size:.8em;margin-left:0">✎ Update</button>`
        }else{
          html += `<button button-edit style="background:#4CCD68;font-size:.8em;margin-left:0">✎ Edit</button> `
        }

        html += `</div></div>`
      }

      html += "</div>";

      body.innerHTML = html
      Array.from(body.querySelectorAll("[button-edit]")).map(el=>{
        el.onclick=()=>parent.edit(parent.datas[OPTIONS.tableName].find(e=>e.id==documenter.select(el).parent("[tr]").getAttribute("tr") ), "update")
      })

      // Sayfa 
      let dataPage = header.querySelector("[data-page]").value
      header.querySelector("[data-page]").innerHTML = (new Array(   Math.ceil( datas._count / parseInt(header.querySelector("[data-count]").value) )   )).fill(0).map((e,i)=>i+1).map(e=>"<option "+(e==dataPage?"selected":"")+">"+e+"</option>")
      if(header.querySelector("[data-page]").innerHTML=="") header.querySelector("[data-page]").innerHTML = "<option selected>1</option>"
      
      // div fix
      Array.from(body.querySelectorAll('[td]')).map(e=>{
        let inner = document.createElement("div")
        while(e.firstChild){
          inner.appendChild(e.firstChild)
        }
        e.appendChild(inner)
      })
      // text
      Array.from(body.querySelectorAll('[td][type=text][editable] div')).map(e=>{
        documenter.TextInput(e)
      })
      // image
      Array.from(body.querySelectorAll('[td][type=image]')).map(e=>{
        e.innerHTML="<img src='"+e.innerText+"'>"
        
      })
      // select
      Array.from(body.querySelectorAll('[td][type=select][editable]')).map(e=>{
        let sel =  e.getAttribute("name")
        let list =  selects[sel]
        let value = e.getAttribute("value")
        let clone = list.cloneNode(true)
        clone.name = e.getAttribute("name")
        clone.style.display=null
        clone.value = value
        e.innerHTML = ""
        e.appendChild( clone )
        clone.oninput = function(){
          let name = e.getAttribute("name")
          let starter = name.split(".")[0] + "."
          Array.from(e.parentElement.querySelectorAll('[td][name]')).filter(e=> e.getAttribute("name")==name || e.getAttribute("name").startsWith(starter) ).map(el=>{
            el.setAttribute(  "value",   clone.value)
            el.querySelector( "select").value = clone.value
          })
        }
      })

      if(OPTIONS.languages.length>0)
        headerLanguageDropdown.oninput()
      
    })
  }

  parent.edit = function(datas={},method="insert"){
    let form = documenter.render(`<form method="post" action='?' table-name="${OPTIONS.tableName}" enctype="multipart/form-data"><input name="${method}" value="${OPTIONS.tableName}" type="hidden" /><input type='hidden' name='json' value='true' /></form>`)
    form.setAttribute("style","width: 100%;background-color: var(--back);z-index: 2;position: absolute;top: 0px;bottom: 0;left: 0;right: 0;margin: 0;padding: 1em;")
    document.querySelector("content").appendChild(form)
    let multiLang = OPTIONS.languages.length > 0 ? true : false
    let languages = multiLang ? OPTIONS.languages : ""
    let currLang  = multiLang ? languages[0] : ""

    
    let grid = documenter.render(`<div grid-form></div>`)

    let languageDropdown;

    if(multiLang){
      languageDropdown = documenter.render(`<dropdown name="language" style="min-width: 200px;margin-left: auto;display: flex;width: 200px;" value="${languages[0]}"></dropdown>`);
      for(let language of languages){
        let name = documenter.organizer.flags.findIndex(e=>e[0]==language)
        name = name==-1 ? language : documenter.organizer.flags[name][2]
        let flag = documenter.organizer.flags.findIndex(e=>e[0]==language)
        flag = flag==-1 ? language : documenter.organizer.flags[flag][1]
        languageDropdown.appendChild(documenter.render(`<div value="${language}"> <img src="https://flagcdn.com/w80/${flag}.png" style="aspect-ratio:3/2"> ${name} </div>`))
      }
      documenter.dropdown(languageDropdown)
      form.appendChild(languageDropdown)
      languageDropdown.oninput=function(){
        let oldLang = currLang;
        let newLang = languageDropdown.value
        
        Array.from(form.querySelectorAll("input[type=text],textarea,iframe[documenter-texteditor]"))
        Array.from(grid.querySelectorAll("input[type=text],textarea")).map(input=>{
          input.value_datas[oldLang] = input.value
          input.value = input.value_datas[newLang]
        })
        Array.from(grid.querySelectorAll("iframe[documenter-texteditor]")).map(iframe=>{
          iframe.textEditor.textarea.value_datas[oldLang] = iframe.textEditor.value
          iframe.textEditor.value = iframe.textEditor.textarea.value_datas[newLang]
        })

        currLang = newLang
        
      }

    }


    form.appendChild(grid)

    if(method=="update"){
      form.appendChild(documenter.render( `<input name="id" value="${datas.id}" type="hidden" />` ))
    }

    let DefaultValue = ""
    if(multiLang){
      DefaultValue = {}
      for(let lang of languages){
        DefaultValue[lang] = ""
      }
      DefaultValue = JSON.stringify( DefaultValue )
    }

    for(let i=0;i<OPTIONS.create.length;i++){
      let full = OPTIONS.create[i] 
      let key = OPTIONS.create[i].split(".")[0]
      let inside = OPTIONS.create[i].split(".")[1]
      
      if(!OPTIONS.titles[key]) console.warn(key)
      
      grid.appendChild(documenter.render(`<label>${OPTIONS.titles[key]}</label>`))
      if(OPTIONS.types[full]=="text"){
        let input = documenter.render(`<input type="text" name="${key}">`)
        let value = datas[key] || DefaultValue
        value = JSON.parse(JSON.stringify(value))
        if(multiLang) value = JSON.parse(value)
        input.value_datas = value
        if(multiLang){ input.value = input.value_datas[currLang] }else{input.value    = value }
        grid.appendChild(input)
      }
      if(OPTIONS.types[full]=="date"){
        let input = documenter.render(`<input type="date" name="${key}">`)
        let value = datas[key] || DefaultValue
        value = JSON.parse(JSON.stringify(value))
        input.value_datas = value
        input.value = value
        grid.appendChild(input)
      }
      if(OPTIONS.types[full]=="select"){
        let select = documenter.render(`<select name="${key}_id"></select>`)
        for(let option of parent.datas[key]){
          select.appendChild( documenter.render("<option value='"+option.id+"'>"+option[inside]+"</option>") )
        }
        let value = datas[key+".id"] || DefaultValue
        select.value = value
        //NO LANGUAGE
        grid.appendChild(select)
      }
      if(OPTIONS.types[full]=="image"){
        let value = datas[key] || "" 
        grid.appendChild(documenter.render(`<input type="hidden" name="${key}" value="${value}">`))
        grid.appendChild(documenter.render(`<img image-selector src="${value?value:'picture.png'}" style="width:100px;height:100px;border:2px dashed #8884;cursor:pointer;object-fit:contain;border-radius:1px;padding:4px;margin: 2px 0px;">`))
      }
      if(OPTIONS.types[full]=="texteditor"){
        let textarea = documenter.render(`<textarea type="hidden" style="height:280px" name="${key}"></textarea>`)
        let value = datas[key] || DefaultValue
        value = JSON.parse(JSON.stringify(value))
        if(multiLang) value = JSON.parse(value)
        textarea.value_datas = value
        if(multiLang){ textarea.value = textarea.value_datas[currLang] }else{ textarea.value    = value }
        grid.appendChild(textarea)
        TextEditor(textarea)
      }
      if(OPTIONS.types[full]=="textarea"){
        let textarea = documenter.render(`<textarea style="height:120px" name="${key}"></textarea>`)
        let value = datas[key] || DefaultValue
        value = JSON.parse(JSON.stringify(value))
        if(multiLang) value = JSON.parse(value)
        textarea.value_datas = value
        if(multiLang){ textarea.value = textarea.value_datas[currLang] }else{ textarea.value    = value }
        grid.appendChild(textarea)
      }
      
    }
   
    form.appendChild(documenter.render(`<space></space>`))
    let footer = documenter.render(`<div flex-x="" gap=""><space></space><div button-cancel button style="background:#F22">Cancel</div><div button-action button style="padding:6px 32px;">＋ ${method=="insert"?"Insert":"Update"}</div></div>`);
    footer.querySelector("[button-cancel]").onclick = ()=> form.remove();
    form.appendChild(footer)



    function ConvertFormData(formElement) {
      let formData = new FormData();
      const formElements = formElement.elements;
      for (let i = 0; i < formElements.length; i++) {
        const currentElement = formElements[i];
        if (currentElement.tagName.toLowerCase() !== 'button' && !currentElement.disabled) {
          if (currentElement.type === 'file') {
            const files = currentElement.files;
            for (let j = 0; j < files.length; j++) {
              formData.append(currentElement.name, files[j]);
            }
          } else if (
            (currentElement.type !== 'radio' && currentElement.type !== 'checkbox') ||
            (currentElement.type === 'radio' && currentElement.checked) ||
            (currentElement.type === 'checkbox' && currentElement.checked)
          ) {
            if(currentElement.value_datas && multiLang){
              formData.append(currentElement.name, JSON.stringify(currentElement.value_datas) );
            }else{
              formData.append(currentElement.name, currentElement.value);
            }
          }
        }
      }
      return formData;
    }

    footer.querySelector("[button-action]").onclick = ()=>{
      if(multiLang) languageDropdown.oninput()
      documenter.post("?",ConvertFormData(form)).then(e=>e.json()).then(e=>{
        documenter.message(e)
        form.remove()
        parent.refresh()
      })
    }
  }

  parent.show(0,10,"id desc")
}

documenter.organizer.flags = [
    ["tr", "tr" , "Turkish"],
    ["en", "gb" , "English"],
    ["de", "de" , "German"],
    ["fr", "fr" , "French"],
    ["it", "it" , "Italian"],
    ["es", "es" , "Spanish"],
    ["pt", "pt" , "Portuguese"],
    ["zh", "cn" , "Mandarin Chinese"],
    ["ja", "jp" , "Japanese"],
    ["ru", "ru" , "Russian"],
    ["ar", "ae" , "Arabic"]
  ]

documenter.organizer.remove = function(el){
  let id = documenter.select(el).parent("[tr]").getAttribute("tr")
  let table = documenter.select(el).parent("[table-name]").getAttribute("table-name").trim()
  
  if( confirm('Silmek istediğinizden emin misiniz?') ){
    documenter.post('?',{
      remove:id,
      table:table
    }).then(e=>e.text()).then(e=>{
      documenter.message(e).style.background='green'
      documenter.select(el).parent("tr,[tr]").remove()
    })
  }
}



documenter.organizer.update = function(el){
  
  let id = documenter.select(el).parent("[tr]").getAttribute("tr")
  let table = documenter.select(el).parent("[table-name]").getAttribute("table-name").trim()
  
  let datas = {
    update:table,
    ...Object.fromEntries( Array.from( documenter.parent(el,"[tr]").querySelectorAll('[td][type=text]')).map(e=>[ e.getAttribute('name').includes(".")? e.getAttribute('name').split(".")[0]+"_id": e.getAttribute('name') , e.innerText ]) ),
    ...Object.fromEntries( Array.from( documenter.parent(el,"[tr]").querySelectorAll('[td][type=select] select')).map(e=>[ e.getAttribute('name').includes(".")? e.getAttribute('name').split(".")[0]+"_id": e.getAttribute('name'), e.value ]) )
  }
  
  datas["_id"] = id
  datas["id"] = datas["id"] || id;

  let image = el.parentElement.parentElement.querySelector('td[type="image"] input[type="file"]')
  
  let form = new FormData();
  for ( var key in datas ) {
    form.append(key, datas[key]);
  }
  if(image) form.append('image',image.files[0])
  
  documenter.post('?',form).then(e=>e.text()).then(e=>{
    documenter.message(e).style.background='green'
  })
  el.previousElementSibling.setAttribute("onclick",`remove(this,"${table}",${datas["id"]})`)
  el.setAttribute("onclick",`update(this,"${table}",${datas["id"]})`)
}






documenter.on("ready",function(){
  Array.from(document.querySelectorAll("documenter-organizer")).map(parent=>{
    documenter.organizer(parent)
  })
})
</script>



<script>
document.addEventListener('DOMContentLoaded',function(){
  message = "<?= addslashes(message()) ?>"
  if(message){
    documenter.message(message).style.background='#4C4'
  }
})
</script>


<!---=======================--->
<!---=========TABLE=========--->
<!---=======================--->


<script>

documenter.parent = function(el,tag){
  el = el.parentElement
  while(!el.matches(tag)){
    el = el.parentElement
    if(el.parentElement==null) break
  }
  return el
}


document.addEventListener("DOMContentLoaded",function(){

  // image
  Array.from(document.querySelectorAll('td[type=image] img')).map(e=>{
    e.style.cursor = "pointer"
    e.style.outline = "2px dashed var(--front)"
    e.style.outlineOffset = "5px"
    let input = document.createElement("input")
    input.setAttribute("type","file")
    input.setAttribute("accept",".jpg, .jpeg, .png")
    input.setAttribute("type","file")
    input.style.display = "none"
    e.parentElement.appendChild(input)
    e.onclick = function(){
      input.click()
    }
    input.onchange = function(){
      var reader = new FileReader()
      reader.onload = function (out) {
        e.setAttribute('src', out.target.result);
      }
      reader.readAsDataURL(input.files[0]);
    }
  })

  // div fix
  Array.from(document.querySelectorAll('td,[td]')).map(e=>{
    let inner = document.createElement("div")
    while(e.firstChild){
      inner.appendChild(e.firstChild)
    }
    e.appendChild(inner)
  })
  // text
  Array.from(document.querySelectorAll('td[type=text] div,[td][type=text] div')).map(e=>{
    documenter.TextInput(e)
  })

  
  // select
  Array.from(document.querySelectorAll('td[type=select],[td][type=select]')).map(e=>{
    let sel =  e.getAttribute("list")
    let list =  document.getElementById( sel )
    let value = e.innerText.trim()
    let clone = list.cloneNode(true)
    clone.name = e.getAttribute("name")
    clone.style.display=null
    clone.value = value
    e.innerHTML = ""
    e.appendChild( clone )
  })

})



function update(el,table,id){
  let datas = {
    update:table,
    ...Object.fromEntries( Array.from( documenter.parent(el,"tr,[tr]") .querySelectorAll('td[type=text],[td][type=text]')).map(e=>[ e.getAttribute('name'), e.innerText ]) ),
    ...Object.fromEntries( Array.from( documenter.parent(el,"tr,[tr]") .querySelectorAll('td[type=select] select,[td][type=select] select')).map(e=>[ e.getAttribute('name'), e.value ]) )
  }
  datas["_id"] = id
  datas["id"] = datas["id"] || id;

  let image = el.parentElement.parentElement.querySelector('td[type="image"] input[type="file"]')
  
  let form = new FormData();
  for ( var key in datas ) {
    form.append(key, datas[key]);
  }
  if(image) form.append('image',image.files[0])
  
  documenter.post('?',form).then(e=>e.text()).then(e=>{
    documenter.message(e).style.background='green'
  })
  el.previousElementSibling.setAttribute("onclick",`remove(this,"${table}",${datas["id"]})`)
  el.setAttribute("onclick",`update(this,"${table}",${datas["id"]})`)
}



function insert(el,insert,id){
  let datas = {
    insert:insert,
    ...Object.fromEntries( Array.from( documenter.parent(el,"tr") .querySelectorAll('td[type=text]')).map(e=>[ e.getAttribute('name'), e.innerText ]) ),
    ...Object.fromEntries( Array.from( documenter.parent(el,"tr") .querySelectorAll('td[type=select] select')).map(e=>[ e.getAttribute('name'), e.value ]) )
  }

  let image = el.parentElement.parentElement.querySelector('td[type="image"] input[type="file"]')
  
  let form = new FormData();
  for ( var key in datas ) {
    form.append(key, datas[key]);
  }
  if(image) form.append('image',image.files[0])
  
  documenter.post('?',form).then(e=>e.text()).then(e=>{
    return location.reload()
    documenter.message(e).style.background='green'
  })
}


function remove(el,table,id){
  if( confirm('Silmek istediğinizden emin misiniz?') ){
    documenter.post('?',{
      remove:id,
      table:table
    }).then(e=>e.text()).then(e=>{
      documenter.message(e).style.background='green'
      documenter.select(el).parent("tr,[tr]").remove()
    })
  }
}

function create(table){

  let clone = document.querySelector('[form="'+table+'"]').cloneNode(true)
  clone.setAttribute("style","width: 100%;background-color: var(--back);z-index: 2;position: absolute;top: 0px;bottom: 0;left: 0;right: 0;margin: 0;")
  document.querySelector("content").appendChild(clone)
  clone.appendChild( documenter.render("<input type='hidden' name='json' value='true'>") )

  clone.querySelector("iframe").remove()
  TextEditor(clone.querySelector(".text-editor"))
  
  clone.querySelector("button").onclick = function(e){
    documenter.post("?",toFormData(clone)).then(e=>e.json()).then(e=>{
      documenter.message("Veri Eklendi!").style.background="green"
      clone.remove()
      let tableElement = document.querySelector('[table="'+table+'"]');
      let data = e
      if(tableElement){
        let th = Array.from(tableElement.querySelectorAll("[th]"))

        let html = "<div tr>" + th.map(e=>{
          if (e.hasAttribute("path")){
            let path = e.getAttribute("path")
            if(path=="id"){
              return `<div td><div> ${data[path]} </div></div>`
            }else{
              return `<div td type="text" name="${path}"><div>${data[path]}</div></div>`
            }
          }
          if(e.hasAttribute("actions")){
            return `<div td actions><div>
              <button onclick='remove(this,"${table}",${data.id})' style="font-size: .8em;background:red;margin-right:0">𐄂 Sil</button> 
              <button onclick='edit("${table}",${data.id})' style="font-size: .8em;background:green;margin-left:0">✓ Güncelle</button> 
            </div></div>`
          }
        }).join("") + "</div>"
        tableElement.insertBefore(documenter.render(html),tableElement.children[1])

      }
    })
    e.preventDefault(); 
  }

  let cancel = documenter.render("<button style='background:#F22'>İptal</button>");
  cancel.onclick = ()=> clone.remove();
  clone.querySelector("button").parentElement.insertBefore( cancel, clone.querySelector("button") );
}


function edit(table,id){

  documenter.post("?",{"get":id,table:table}).then(e=>e.json()).then(data=>{

      let clone = document.querySelector('[form="'+table+'"]').cloneNode(true)
      clone.setAttribute("style","width: 100%;background-color: var(--back);z-index: 2;position: absolute;top: 0px;bottom: 0;left: 0;right: 0;margin: 0;")
      document.querySelector("content").appendChild(clone)

      

      for(name in data){
        let el = clone.querySelector("[name="+name+"]")
        if(el){
          if(el.getAttribute("type")=="file") continue
          el.value=data[name]
        }
      }

      if(data.image!=null) clone.querySelector("[name=image] + img").src = data.image

      clone.querySelector("iframe").remove()
      TextEditor(clone.querySelector(".text-editor"))
      
      clone.querySelector("button").innerHTML = "✓ Güncelle"
      clone.querySelector("[name=insert]").setAttribute("name","update")
      clone.querySelector("[name=update]").setAttribute("value",table)

      clone.appendChild( documenter.render("<input type='hidden' name='id' value='"+id+"'>") )
      
      clone.querySelector("button").onclick = function(e){
        documenter.post("?",toFormData(clone)).then(e=>e.text()).then(e=>{
          documenter.message(e).style.background="green"
          Array.from(clone.elements).map(e=>{ 
            if(e.type=="text") { Array.from(document.querySelectorAll("[path='"+table+"/"+id+"/"+e.name+"']")).map(el=>el.firstElementChild.innerText = e.value) }
          })
          clone.remove()
        })
        e.preventDefault(); 
      }

      let cancel = documenter.render("<button style='background:#F22'>İptal</button>");
      cancel.onclick = ()=> clone.remove();
      clone.querySelector("button").parentElement.insertBefore( cancel, clone.querySelector("button") );
      
  })
}


function toFormData(formElement) {
  let formData = new FormData();
  const formElements = formElement.elements;
  for (let i = 0; i < formElements.length; i++) {
    const currentElement = formElements[i];
    if (currentElement.tagName.toLowerCase() !== 'button' && !currentElement.disabled) {
      if (currentElement.type === 'file') {
        const files = currentElement.files;
        for (let j = 0; j < files.length; j++) {
          formData.append(currentElement.name, files[j]);
        }
      } else if (
        (currentElement.type !== 'radio' && currentElement.type !== 'checkbox') ||
        (currentElement.type === 'radio' && currentElement.checked) ||
        (currentElement.type === 'checkbox' && currentElement.checked)
      ) {
        formData.append(currentElement.name, currentElement.value);
      }
    }
  }
  return formData;
}





</script>




<script>



TextEditor_head = `
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chivo:wght@300;500;900&display=swap" rel="stylesheet">
<style>
  
  body{
    padding-top:.5em;
    background:white;
  }
  body,body p{
    font-family:'Chivo', sans-serif!important;
    text-align:justify!important;
    text-indent: 2em;
    color:inherit;
    line-height:1.25!important;
  }
  body h1,body h2,body h3{
    text-indent:0;
    color: black;
    font-size:1em;
    margin-bottom:.5em;
  }
  body h1::before,body h2::before,body h3::before{
    content: "➤ "
  }

  body p img{
    margin-left:-2em;
  }
  body img{
    margin:auto;
    margin-top:0;
    margin-bottom:1em;
    display:block;
    max-width:50%;
  }
  body a{
    color:#008;
  }
  ol,ul{
    padding-left: 2em;
    text-indent: 0;
  }
</style>
`

function TextEditor(element){
  let textEditor = documenter.TextEditor(element, { 
    head: TextEditor_head ,
    toolbar           : ["formatBlock","bold","italic","underline","strikeThrough","insertUnorderedList","insertOrderedList","space"],
    allowedProperties : ["font-weight","font-style"],
    allowedAttributes : ["style","src","alt"]
  })
  let item = document.createElement("div")
  
  item.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Z"/></svg>Resim Ekle'
  if(textEditor.toolbar) textEditor.toolbar.appendChild(item)
  item.onclick=()=>{
    fileManager = FileManager.Select("image",{
      path : "/",
      closeButton : true,
      theme: "light",
      language: "tr"
    }).then(e=>{
      console.log(e)
      let img = document.createElement("img")
      img.src = options.link +"uploads" + e
      textEditor.body.appendChild(img)
    })
  }
  window.textEditor = textEditor
}


documenter.on("ready",function(){
  //TextEditor(document.querySelector(".text-editor"))
})



documenter.on("click","[image-selector]",function(){
  fileManager = FileManager.Select("image",{
    path : "/",
    closeButton : true,
    theme: "light",
    language: "tr"
  }).then(e=>{
    let src = options.link + "uploads" + e
    this.src = src
    this.previousElementSibling.value  = src
  })
})




function Images(){
  fileManager = FileManager.Select({
    type: "explore",
    path : "/",
    closeButton : true,
    theme: "light",
    language: "tr",
    title: "Görselleri İncele"
  })
}



function WFA(text,discard=[]){
    
  function calc(words){
    words  = words.sort()
    one    = [... new Set(words)] 
    response = []
    lastChar = words[0]
    lastIndex = 0
    for(let i = 0; i < words.length ; i++){
      if( words[i] != lastChar ){
        response.push([lastChar,i-lastIndex])
        lastChar  = words[i]
        lastIndex = i
      }
    }
    return response
  }

  text = text.toLowerCase()
  
  words  = text.match(/[\wığüşiöç]+/g)

  if(words==null) return [[],[],[]]
  // One Words
  
  _1 = calc(words.filter(e=>discard.indexOf(e)==-1))
  _1_ = _1.sort((a,b)=>a[1]-b[1]>0?-1:1)

  // Two Words
  words_2 = words.map((e,i,a)=>e+" "+a[i+1])
  words_2.pop()
  _2 = calc(words_2)
  _2_ = _2.sort((a,b)=>a[1]-b[1]>0?-1:1)

  // Three Words
  words_3 = words.map((e,i,a)=>e+" "+a[i+1]+" "+a[i+2])
  words_3.pop()
  words_3.pop()
  _3 = calc(words_3)
  _3_ = _3.sort((a,b)=>a[1]-b[1]>0?-1:1) 

  return [_1_ , _2_, _3_]

}


</script>



