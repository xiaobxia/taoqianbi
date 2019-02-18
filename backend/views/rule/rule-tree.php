<?php
    use common\helpers\Url;
?>

<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/gojs/1.6.7/go-debug.js"></script> -->


<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script src="<?php echo Url::toStatic('/js/gojs/go.js'); ?>"></script>

<style type="text/css">

  .inspector {
    display: inline-block;
    font: bold 14px helvetica, sans-serif;
    background-color: #212121; /* Grey 900 */
    color: #F5F5F5; /* Grey 100 */
    cursor: default;
  }

  .inspector input {
    background-color: #424242; /* Grey 800 */
    color: #F5F5F5; /* Grey 100 */
    font: bold 12px helvetica, sans-serif;
    border: 0px;
    padding: 2px;
  }

  .inspector input:disabled {
    background-color: #BDBDBD; /* Grey 400 */
    color: #616161; /* Grey 700 */
  }

</style>


<div id="sample">
  <div id="myDiagramDiv" style="background-color: #696969; border: solid 1px black; height: 800px"></div>
  <div>
    <div id="myInspector" class="inspector">

    </div>
    <button id="save">保存</button>
  </div>
</div>


<script type="text/javascript">

  $(function(){
    getTreeConstruct(25);
    // showTree();

  });

  function getTreeConstruct(id){
    $.getJSON(
      "<?php echo Url::toRoute('rule-json/get-tree-construct') ?>",
      {
        id: id
      },
      function(ret){
        if (ret.code != 0) {
          alert(ret.message);
          return;
        }

        // var str = "";
        // for(var i in ret.data){
        //  str += "<tr><<th width=\"110px;\" class=\"person\">" + ret.data[i]['name'] + "</th>";
        //  str += "<td style=\"padding: 2px;margin-bottom: 1px; border:1px solid darkgray;color:#ff5951;cursor: pointer\">";
        //  str += "点击查看</td></tr>";
        // }
        // $("#tree-table").append(str);
        showTree(formatTree(ret.data));
      }
    );
  }

  function createNewNode(parent_id, temp_id, callback){
    $.post(
      "<?php echo Url::toRoute('rule-json/create-node'); ?>,
      {
        parent_id : parent_id,
        temp_id : temp_id
      },
      function(ret){
        if (ret.code != 0) {
          alert(ret.message);
          return;
        }
        callback(ret.data);
      },
      'json'
    )
  }

  function addLink(node_id, parent_id, success){
    $.post(
      "<?php echo Url::toRoute('rule-json/create-node-relation'); ?>",
      {
        parent_id : parent_id,
        node_id : node_id
      },
      function(ret){
        if (ret.code != 0) {
          alert(ret.message);
          return;
        }
        success();
      },
      'json'
    )
  }

  function deleteNode(node_id, success){
    $.post(
      "<?php echo Url::toRoute('rule-json/remove-node'); ?>",
      {
        node_id : node_id
      },
      function(ret){
        if (ret.code != 0) {
          alert(ret.message);
          return;
        }
        success();
      },
      'json'
    )
  }

  function formatTree(data){
    var result = [];
    function format(d, parent){
      var node = { key: d.id,  name: d.name, weight : (d.weight / 100).toFixed(2) + "%"};
      if (parent) {
        node.parent = parent;
      }
      result.push(node);
      if (d.children) {
        for(var i in d.children){
          format(d.children[i], d.id);
        }
      }
    }
    format(data);
    return result;
  }

  function showEditTable(data){
    $table = $("<table></table>");
    for(var i in data){
      $table.append("<tr><td>" + i + "</td><td><input data-key='" + i + "' value='" + data[i] + "'></td></tr>");
    }
    $("#myInspector").html($table);
    $("#save").show().off("click");
    $("#save").on("click", function(){
      var d = {};
      $("#myInspector input").each(function(e, i){
        d[$(i).data('key')] = $(i).val();
      });
      editNode(d, function(){
        alert("修改成功");
        location.reload();
      }, function(){
        alert("修改失败");
      });

    });
  }

  function editNode(data, success, fail){
    $.post(
      "<?php echo Url::toRoute('rule-json/modify-node'); ?>",
      {
        node_id : data['key'],
        parent_id : data['parent'],
        name : data['name'],
        weight : parseFloat(data['weight']) * 100
      },
      function(ret){
        if (ret.code != 0) {
          return fail();
        }
        success()
      },
      'json'
    )
  }

  function showTree(data) {
    if (window.goSamples) goSamples();  // init for these samples -- you don't need to call this
    var $ = go.GraphObject.make;  // for conciseness in defining templates
    var myDiagram =
        $(go.Diagram, "myDiagramDiv", // must be the ID or reference to div
          {
              initialContentAlignment: go.Spot.Center,
          maxSelectionCount: 1, // users can select only one part at a time
          validCycle: go.Diagram.CycleDestinationTree, // make sure users can only create trees
          "clickCreatingTool.archetypeNodeData": {}, // allow double-click in background to create a new node
          "clickCreatingTool.insertPart": function(loc) {  // customize the data for the new node
            var self = this;

            createNewNode(0, 0, function(data){
              self.archetypeNodeData = {
                key: data['id'], // assign the key based on the number of nodes
                name: data['name'],
                weight:  (data['weight'] / 100).toFixed(2) + "%"
              };
              return go.ClickCreatingTool.prototype.insertPart.call(self, loc);
            });

          },
          layout:
            $(go.TreeLayout,
              {
                treeStyle: go.TreeLayout.StyleLastParents,
                arrangement: go.TreeLayout.ArrangementHorizontal,
                // properties for most of the tree:
                angle: 90,
                layerSpacing: 35,
                // properties for the "last parents":
                alternateAngle: 90,
                alternateLayerSpacing: 35,
                alternateAlignment: go.TreeLayout.AlignmentBus,
                alternateNodeSpacing: 20
            }),
          "undoManager.isEnabled": true // enable undo & redo
      });

    // when the document is modified, add a "*" to the title and enable the "Save" button
    // myDiagram.addDiagramListener("Modified", function(e) {
    //  var button = document.getElementById("SaveButton");
    //  if (button) button.disabled = !myDiagram.isModified;
    //  var idx = document.title.indexOf("*");
    //  if (myDiagram.isModified) {
    //    if (idx < 0) document.title += "*";
    //  } else {
    //    if (idx >= 0) document.title = document.title.substr(0, idx);
    //  }
    // });

    myDiagram.addDiagramListener("TextEdited", function(e) {
      console.log(e.subject, e.parameter);
    });

    // manage boss info manually when a node or link is deleted from the diagram
    myDiagram.addDiagramListener("SelectionDeleting", function(e) {
      var part = e.subject.first(); // e.subject is the myDiagram.selection collection,
      // so we'll get the first since we know we only have one selection
      deleteNode(part.data.key, function(){
        myDiagram.startTransaction("clear boss");
        if (part instanceof go.Node) {
          var it = part.findTreeChildrenNodes(); // find all child nodes
          while(it.next()) { // now iterate through them and clear out the boss information
            var child = it.value;
            var bossText = child.findObject("boss"); // since the boss TextBlock is named, we can access it by name
            if (bossText === null) return;
            bossText.text = undefined;
          }
        } else if (part instanceof go.Link) {
          var child = part.toNode;
          var bossText = child.findObject("boss"); // since the boss TextBlock is named, we can access it by name
          if (bossText === null) return;
            bossText.text = undefined;
          }
          myDiagram.commitTransaction("clear boss");
        });
      })



    var levelColors = ["#AC193D/#BF1E4B", "#2672EC/#2E8DEF", "#8C0095/#A700AE", "#5133AB/#643EBF",
      "#008299/#00A0B1", "#D24726/#DC572E", "#008A00/#00A600", "#094AB2/#0A5BC4"];

    // override TreeLayout.commitNodes to also modify the background brush based on the tree depth level
    myDiagram.layout.commitNodes = function() {
      go.TreeLayout.prototype.commitNodes.call(myDiagram.layout);  // do the standard behavior
      // then go through all of the vertexes and set their corresponding node's Shape.fill
      // to a brush dependent on the TreeVertex.level value
      myDiagram.layout.network.vertexes.each(function(v) {
        if (v.node) {
          var level = v.level % (levelColors.length);
          var colors = levelColors[level].split("/");
          var shape = v.node.findObject("SHAPE");
          if (shape) shape.fill = $(go.Brush, "Linear", { 0: colors[0], 1: colors[1], start: go.Spot.Left, end: go.Spot.Right });
        }
      });
    };

    // when a node is double-clicked, add a child to it
    function nodeDoubleClick(e, obj) {
      var clicked = obj.part;
      if (clicked !== null) {
        var thisemp = clicked.data;
        console.log(thisemp);
        myDiagram.startTransaction("add employee");
        createNewNode(thisemp['key'], 0 , function(data){
          var newemp = { key: data['id'], name: data['name'], weight: (data['weight'] / 100).toFixed(2) + "%", parent: thisemp.key };
          myDiagram.model.addNodeData(newemp);
          myDiagram.commitTransaction("add employee");
        })

      }
    }

    function nodeClick(e, obj) {
      var clicked = obj.part;
      if (clicked !== null) {
        var thisemp = clicked.data;
        showEditTable(thisemp);
      }
    }

    // this is used to determine feedback during drags
    function mayWorkFor(node1, node2) {
      if (!(node1 instanceof go.Node)) return false;  // must be a Node
      if (node1 === node2) return false;  // cannot work for yourself
      if (node2.isInTreeOf(node1)) return false;  // cannot work for someone who works for you
      return true;
    }

    // This function provides a common style for most of the TextBlocks.
    // Some of these values may be overridden in a particular TextBlock.
    function textStyle() {
      return { font: "9pt  Segoe UI,sans-serif", stroke: "white" };
    }

    // define the Node template
    myDiagram.nodeTemplate =
      $(go.Node, "Auto",
        { doubleClick: nodeDoubleClick , click: nodeClick},
        { // handle dragging a Node onto a Node to (maybe) change the reporting relationship
          mouseDragEnter: function (e, node, prev) {
            var diagram = node.diagram;
            var selnode = diagram.selection.first();
            if (!mayWorkFor(selnode, node)) return;
            var shape = node.findObject("SHAPE");
            if (shape) {
              shape._prevFill = shape.fill;  // remember the original brush
              shape.fill = "darkred";
            }
          },
          mouseDragLeave: function (e, node, next) {
            var shape = node.findObject("SHAPE");
            if (shape && shape._prevFill) {
              shape.fill = shape._prevFill;  // restore the original brush
            }
          },
          mouseDrop: function (e, node) {
            var diagram = node.diagram;
            var selnode = diagram.selection.first();  // assume just one Node in selection
            if (mayWorkFor(selnode, node)) {
              console.log(selnode.part.data, node.part.data);

              addLink(selnode.part.data.key, node.part.data.key, function(){
                // find any existing link into the selected node
                var link = selnode.findTreeParentLink();
                if (link !== null) {  // reconnect any existing link
                  link.fromNode = node;
                } else {  // else create a new link
                  diagram.toolManager.linkingTool.insertLink(node, node.port, selnode, selnode.port);
                }
              })


            }
          }
        },
        // for sorting, have the Node.text be the data.name
        new go.Binding("text", "name"),
        // bind the Part.layerName to control the Node's layer depending on whether it isSelected
        new go.Binding("layerName", "isSelected", function(sel) { return sel ? "Foreground" : ""; }).ofObject(),
        // define the node's outer shape
        $(go.Shape, "Rectangle",
          {
            name: "SHAPE", fill: "white", stroke: null,
            // set the port properties:
            portId: "", fromLinkable: true, toLinkable: true, cursor: "pointer"
          }
        ),
        $(go.Panel, "Horizontal",
          // $(go.Picture,
          //  {
          //    name: 'Picture',
          //    desiredSize: new go.Size(39, 50),
          //    margin: new go.Margin(6, 8, 6, 10),
          //  },
          //  new go.Binding("source", "key", findHeadShot)
          // ),
          // define the panel where the text will appear
          $(go.Panel, "Table",
            {
              maxSize: new go.Size(150, 999),
              margin: new go.Margin(6, 10, 0, 3),
              defaultAlignment: go.Spot.Left
            },
            $(go.RowColumnDefinition, { column: 2, width: 4 }),
            $(go.TextBlock, textStyle(),  // the name
              {
                row: 0, column: 0, columnSpan: 5,
                font: "12pt Segoe UI,sans-serif",
                editable: true, isMultiline: false,
                minSize: new go.Size(10, 16)
              },
              new go.Binding("text", "name").makeTwoWay()),
            $(go.TextBlock, "权重: ", textStyle(),
              { row: 1, column: 0 }),
            $(go.TextBlock, textStyle(),
              {
                row: 1, column: 1, columnSpan: 4,
                editable: true, isMultiline: false,
                minSize: new go.Size(10, 14),
                margin: new go.Margin(0, 0, 0, 3)
              },
              new go.Binding("text", "weight").makeTwoWay()),
            $(go.TextBlock, textStyle(),
              { row: 2, column: 0 },
              new go.Binding("text", "key", function(v) {return "ID: " + v;})),
            // $(go.TextBlock, textStyle(),
            //  { name: "boss", row: 2, column: 3, }, // we include a name so we can access this TextBlock when deleting Nodes/Links
            //  new go.Binding("text", "parent", function(v) {return "Boss: " + v;})),
            $(go.TextBlock, textStyle(),  // the comments
              {
                row: 3, column: 0, columnSpan: 5,
                font: "italic 9pt sans-serif",
                wrap: go.TextBlock.WrapFit,
                editable: true,  // by default newlines are allowed
                minSize: new go.Size(10, 14)
              },
              new go.Binding("text", "comments").makeTwoWay())
          )  // end Table Panel
        ) // end Horizontal Panel
      );  // end Node

    // the context menu allows users to make a position vacant,
    // remove a role and reassign the subtree, or remove a department
    myDiagram.nodeTemplate.contextMenu =
      $(go.Adornment, "Vertical",
        $("ContextMenuButton",
          $(go.TextBlock, "Vacate Position"),
          {
            click: function(e, obj) {
              var node = obj.part.adornedPart;
              if (node !== null) {
                var thisemp = node.data;
                myDiagram.startTransaction("vacate");
                // update the key, name, and comments
                myDiagram.model.setKeyForNodeData(thisemp, getNextKey());
                myDiagram.model.setDataProperty(thisemp, "name", "(Vacant)");
                myDiagram.model.setDataProperty(thisemp, "comments", "");
                myDiagram.commitTransaction("vacate");
              }
            }
          }
        ),
        $("ContextMenuButton",
          $(go.TextBlock, "Remove Role"),
          {
            click: function(e, obj) {
              // reparent the subtree to this node's boss, then remove the node
              var node = obj.part.adornedPart;
              if (node !== null) {
                myDiagram.startTransaction("reparent remove");
                var chl = node.findTreeChildrenNodes();
                // iterate through the children and set their parent key to our selected node's parent key
                while(chl.next()) {
                  var emp = chl.value;
                  myDiagram.model.setParentKeyForNodeData(emp.data, node.findTreeParentNode().data.key);
                }
                // and now remove the selected node itself
                myDiagram.model.removeNodeData(node.data);
                myDiagram.commitTransaction("reparent remove");
              }
            }
          }
        ),
        $("ContextMenuButton",
          $(go.TextBlock, "Remove Department"),
          {
            click: function(e, obj) {
              // remove the whole subtree, including the node itself
              var node = obj.part.adornedPart;
              if (node !== null) {
                myDiagram.startTransaction("remove dept");
                myDiagram.removeParts(node.findTreeParts());
                myDiagram.commitTransaction("remove dept");
              }
            }
          }
        )
      );

    // define the Link template
    myDiagram.linkTemplate =
      $(go.Link, go.Link.Orthogonal,
        { corner: 5, relinkableFrom: true, relinkableTo: true },
        $(go.Shape, { strokeWidth: 4, stroke: "#00a4a4" }));  // the link shape

    // read in the JSON-format data from the "mySavedModel" element
    // myDiagram.model = go.Model.fromJson(document.getElementById("mySavedModel").value);

    var model = $(go.TreeModel);
    model.nodeDataArray = data;
    myDiagram.model = model;



    // support editing the properties of the selected person in HTML
    // if (window.Inspector) {
    //  myInspector = new Inspector('myInspector', myDiagram, { properties: {'key': { readOnly: true }}});
    // }
  }

</script>