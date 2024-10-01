import { commonTasks } from "./commonTask.js";

const priorityCheck = {
  "list-group-item-danger": "High Priority",
  "list-group-item-warning": "Medium priority",
  "list-group-item-primary": "Low priority",
};
const priorityColor = {
  "high-priority": "danger",
  "medium-priority": "warning",
  "low-priority": "primary",
};
$(document).ready(function () {
  toastr.options = {
    newestOnTop: false,
    preventDuplicates: true,
    timeOut: 2000,
  };

  const container = $(".fireworks-container")[0];
  const fireworks = new Fireworks.default(container,{ sound: {
    enabled: true,
    files: [
      'https://fireworks.js.org/sounds/explosion0.mp3',
      'https://fireworks.js.org/sounds/explosion1.mp3',
      'https://fireworks.js.org/sounds/explosion2.mp3'
    ],
    volume: {
      min: 2,
      max: 4
    }
  }});
  // fireworks.start();

  $('[data-toggle="tooltip"]').tooltip({ placement: "top" }); // bootsrap  tooltip initialize

  let storeLiState = "";

  const autocompletDropdownWidth = $("#input-width")[0].offsetWidth;

  $("#todo-input").autocomplete({}); //autocomplete initialize for todo-input
  $(".ui-autocomplete").width(autocompletDropdownWidth);
  jQuery.ui.autocomplete.prototype._resizeMenu = function () {
    $(".ui-autocomplete").css({ width: autocompletDropdownWidth });
  };
  //Enter key press to add todo
  $("#todo-input")
    .on("keydown", function (event) {
      if (event.keyCode == 13) {
        event.preventDefault();

        addTodo(event);
      }
    })
    .autocomplete({
      minLength: 2,
      source: commonTasks,
    });

  $(".add-todo").click(addTodo); //adding todo using priority btns or add-btn

  // todo Add function
  function addTodo(event, todo) {
    if ($(".task-store").css("display") == "none") {
      $(".task-store").css("display", "block ");
    }
    let todoInput = $("#todo-input");
    todo = todo || todoInput.val();
    todoInput.val("");
    let todoTitle = todo.length > 30 ? todo : "";

    if (todo) {
      let listBg = findListBg(event.target);
      const liTagHtml1 = `  <li
      class="list-group-item align-center ${listBg}"
      title= "${todoTitle}"
      data-toggle="tooltip"
    >
      <span>
      ${todo}
      </span>
      <div class="priority d-none">
        <div type="button" class="rounded-circle bg-danger high-priority" title="High Priority" ></div>
        <div type="button" class="rounded-circle bg-warning medium-priority" title="Medium Priority" ></div>
        <div  type="button" class="rounded-circle bg-primary low-priority " title="Low Priority" ></div>
        <div type="button" class="rounded-circle bg-light border" title="No Prority" ></div>
      </div>
      <div class=" action-btns float-right d-none">
        <button
          type="button"
          title="edit todo task"
        
          class="btn btn-xs btn-outline-primary btn-edit"
        >
          <i class="fa fa-pencil" aria-hidden="true"></i>
        </button>

        <button
          type="button"
          title="delete todo task"
        
          class="btn btn-xs btn-outline-danger btn-delete"
        >
          <i class="fa fa-trash" aria-hidden="true"></i>
        </button>
      </div>
    </li>`;
      const color = listBg.split("-")[listBg.split("-").length - 1];
      const priority = listBg ? priorityCheck[listBg] : "";

      const priorirtyInfo = listBg
        ? `<br>with <u class=" text-sm">${priority}</u>`
        : "";

      toastr.success(todo, "Task is added in todo List" + priorirtyInfo, {
        positionClass: "toast-top-full-width",
      });
      $("#uncompleted-tasks-list").append(liTagHtml1);

      arrangeUncompletedTodo();
    } else {
      alert("Please Write todo in input box");
      return false;
    }
    return true;
  }

  // find class of btn
  function findListBg(clickedEle) {
    let listBg = "";

    if ($(clickedEle).hasClass("high-priority")) {
      listBg = "list-group-item-danger";
    } else if ($(clickedEle).hasClass("medium-priority")) {
      listBg = "list-group-item-warning";
    } else if ($(clickedEle).hasClass("low-priority")) {
      listBg = "list-group-item-primary";
    }

    return listBg;
  }

  // Arrange uncompleted todo based on prority
  function arrangeCompletedTodo() {
    checkSaveUncomplete();
    let nonSuccessLi = $(
      "#completed-tasks-list li:not(.list-group-item-success)"
    );

    if (nonSuccessLi.length) {
      addCompleteTask(nonSuccessLi[0]);
      $(nonSuccessLi).remove();
    }
  }
  function checkSaveUncomplete() {
    var list = $("#completed-tasks-list");
    var items = list.children("li").get();
    items.forEach((element) => {
      let saveBtn = $(element).find(".btn-save");
      if (saveBtn.length) saveTodo(saveBtn);
    });
  }
  function arrangeUncompletedTodo(event) {
    let successLi = $("#uncompleted-tasks-list li.list-group-item-success");
    if (successLi.length) {
      let child = $(successLi).children("span");
      selectPriority(child);
    }
    var list = $("#uncompleted-tasks-list");
    var items = list.children("li").get();

    // Define a custom sorting function based on class attribute values
    var classOrder = [
      "list-group-item-danger",
      "list-group-item-warning",
      "list-group-item-primary",
      "list-group-item",
    ];

    items.sort(function (a, b) {
      var classA = $(a).attr("class").split(" ");
      var classB = $(b).attr("class").split(" ");
      classA = classOrder.findIndex((element) => classA.includes(element));
      classB = classOrder.findIndex((element) => classB.includes(element));
      return classA - classB;
    });

    // Clear the existing list
    list.empty();

    // Append the sorted list items back to the <ul>
    for (var i = 0; i < items.length; i++) {
      list.append(items[i]);
    }
  }

  // Double click to completed task
  $(document).on("dblclick", "#uncompleted-tasks-list li", function () {
    //save Todo call
    $(this).tooltip("hide");
    let saveBtn = $(this).find(".btn-save");
    if (saveBtn.length) saveTodo(saveBtn);
    addCompleteTask(this);
  });

  function addCompleteTask(ele) {
    let completedTodo = $(ele).text().trim();
    let todoTitle = completedTodo.length > 30 ? completedTodo : "";

    const liTagHtml2 = `<li class="list-group-item list-group-item-success" title="${todoTitle}">
    <span>
    ${completedTodo}
    </span>
<div class="action-btns float-right d-none">
    <button type="button" title="repeat todo task"
   
        class="btn btn-xs btn-outline-warning btn-repeat">
        <i class="fa fa-repeat" aria-hidden="true"></i>
    </button>
    <button type="button" title="delete todo task"
   
        class="btn btn-xs btn-outline-danger btn-delete">
        <i class="fa fa-trash" aria-hidden="true"></i>
    </button>
</div>
</li>`;
    $("#completed-tasks-list").prepend(liTagHtml2);
    $(ele).css("text-decoration", "line-through").fadeOut("slow");

    toastr.success(completedTodo, "Your task is Completed", {
      positionClass: "toast-top-full-width",
      timeOut: 1000,
    });
    setTimeout(function () {
      $(ele).remove();
      let items = $("#uncompleted-tasks-list").children("li").length;
      if (!items) {
        handleSweetAlert(); //call sweet alert
      }
    }, 500);
  }

  //Highliting the hoverd li tag
  $(document).on("mouseenter mouseleave", ".list-group-item", function (event) {
    let tooltipBg = $(this).css("background-color");
    let tooltipColor = $(this).css("color");
    $('[data-toggle="tooltip"]').tooltip({ placement: "top" });
    // changing tooltip css

    if (event.type === "mouseenter") {
      $(".list-group-item").css("opacity", "0.6");

      let contentLength = $(this).children("span").text().trim().length;

      if (contentLength > 30) {
        $(this).tooltip("show");

        const tooltipWidth = this.offsetWidth;

        $(".tooltip-inner ").css({
          backgroundColor: tooltipBg,
          color: tooltipColor,
          border: "2px solid #f78d4c",
          "max-width": tooltipWidth,
        });
        $(".tooltip .arrow ").css({ color: "#f78d4c" });
      } else {
        $(this).tooltip("hide");
      }
    } else if (event.type === "mouseleave") {
      $(".list-group-item").css("opacity", "1");
      $(this).tooltip("hide");
      arrangeUncompletedTodo();
      arrangeCompletedTodo();

      //save Todo call
      // let saveBtn = $(this).find(".btn-save");
      // if (saveBtn.length) saveTodo(saveBtn);
    }
  });
  function handleSweetAlert() {
    let messageDiv = document.createElement("div");
    let messageHtml = `<div><p >You completed All tasks!</p><p><u>Do you want to continue</u></p><div>`;

    $(messageDiv).html(messageHtml);
    fireworks.start();
    $(".fireworks-container ").css("display", "block");

    swal({
      title: "Good job!",
      content: messageDiv,
      // text: "You completed All tasks! <br> Do you want to continue",
      icon: "success",
      button: "Yes",
    }).then((value) => {
      fireworks.stop();
      $(".fireworks-container ").css("display", "none");
    });
  }

  // priority edit
  $(document).on("click", ".priority", function (event) {
    var clickedPriority = $(event.target);

    if ($(clickedPriority).hasClass("rounded-circle")) {
      let selectedBg = findListBg(event.target);

      let removedClasses = [
        "list-group-item-danger",
        "list-group-item-warning",
        "list-group-item-primary",
      ];
      $(clickedPriority)
        .parents("li")
        .removeClass(removedClasses, 100)
        .addClass(selectedBg, 110);

      //save Todo call
      let saveBtn = $(this).parent("li").find(".btn-save");
      if (saveBtn.length) saveTodo(saveBtn, selectedBg);
      else {
        const content = $(this).siblings("span").text().trim();

        const priority = selectedBg ? priorityCheck[selectedBg] : "";

        const priorirtyInfo = selectedBg
          ? `Now Task has <u class=" text-sm">${priority}</u>`
          : "Now your task has No Priority";

        // const messageWithColor=priority?`<span  class="text-${color}">${message}</span>`:message
        toastr.info(content, priorirtyInfo, {
          positionClass: "toast-bottom-full-width",
        });
      }
    }
  });

  //   edit functionality
  $(document).on("click", ".btn-edit", function () {
    let editableTask = $(this).parents("div").siblings("span");
    let selectedLi = $(this).parents("li");
    let content = editableTask.text().trim();
    if (content.length < 30) {
      const liEditHtml = $("<input>", {
        type: "text",

        class: " border-0 bg-transparent li-edit-input",
      });
      $(editableTask).replaceWith(liEditHtml);
      $(liEditHtml).focus();
      $(liEditHtml).val(content);
      const saveBtnHtml = ` <button
    type="button"
    title="save todo task"
    class="btn btn-xs btn-outline-secondary  btn-save"
  >
    <i class="fa  fa-floppy-o " aria-hidden="true"></i>
  </button>`;

      $(this).replaceWith(saveBtnHtml);
    } else {
      //modal Edit function
      $("#editTodoModal").modal("show");
      const editableTextarea = $("#editTodoModal textarea");
      editableTextarea.val(content);
      editableTextarea.height(editableTextarea[0].scrollHeight);

      $("#save-modal-edit").click(function () {
        const liText = editableTextarea.val().trim();

        toastr.info(liText, "Your task is succesfully edited", {
          positionClass: "toast-bottom-full-width",
        });
        editableTask.text(liText);
        let titleContent = liText.length > 30 ? liText : "";

        $(selectedLi).attr("title", titleContent);

        $("#editTodoModal").modal("hide");
      });
    }
  });

  // delete functionality
  $(document).on("click", ".btn-delete", function () {
    const selectedLi = $(this).parents("li");
    $(selectedLi).tooltip("hide");
    let deletedTaskSection = $(this)
      .parents("ul")
      .siblings("h5")
      .text()
      .trim()
      .split(" ")[0];
    let deleteableTask = $(this).parents("div").siblings("span");
    let content = deleteableTask.text().trim();

    $("#deleteConfirmationModalLabel").text(
      "Confirm to Delete this " + deletedTaskSection + " task"
    );
    $("#selected-delete-task").text(content);

    $("#deleteConfirmationModal").modal("show");
    $("#deleteConfirmationModal .btn-danger").click(function () {
      deleteTodo(selectedLi);
    });
  });

  // $("#uncompleted-tasks-list").sortable({
  //   placeholder: "ui-state-highlight",
  //   containment: "parent",
  //   //   revert: true
  // });
  $("#uncompleted-tasks-list, #completed-tasks-list")
    .sortable({
      connectWith: ".todo-list",
      placeholder: "ui-state-highlight",
      //  containment: "parent",
    })
    .disableSelection();

  // save todo or edited task
  $(document).on("click", ".btn-save", function () {
    saveTodo(this);
  });

  $(document).on("click", ".btn-repeat", function () {
    selectPriority(this);
  });

  function selectPriority(event) {
    const selectedLi = $(event).parents("li");
    storeLiState = selectedLi;

    let todoTask = $(selectedLi).children("span").text();
    $("#setPriorityModal .modal-body h5").text(todoTask);

    $("#setPriorityModal").modal("show");
  }

  $(".modal-priority-btn").click(function (e) {
    let todoTask = $(storeLiState).children("span").text();
    $(storeLiState).remove();
    const clickedPriority = $(e.target);

    if ($(clickedPriority).hasClass("rounded-circle")) {
      let listBg = findListBg(storeLiState);

      const checkCorrect = addTodo(e, todoTask);
      if (!checkCorrect) {
        wrongMeassage();
      } else {
        const message = clickedPriority.attr("title");
        const color = listBg.split("-")[listBg.split("-").length - 1];
        const priority = listBg ? priorityCheck[listBg] : "";
        const messageWithColor = priority
          ? `<span  class="text-${color}">${message}</span>`
          : message;
        toastr.info(messageWithColor, "Task added in Uncompleted List", {
          positionClass: "toast-top-full-width",        
          timeOut: 1000,
        });
      }
      $("#setPriorityModal").modal("hide");
    }
  });
  //delete todo function
  function deleteTodo(ele) {
    $(ele).css("text-decoration", "line-through").fadeOut(1000);
    setTimeout(()=>{
      $(ele).remove();
      toastr.success("", "Your task is succesfully deleted", {
        positionClass: "toast-top-full-width",
        timeOut: 1000,
      });
    },1000)
   
    $("#deleteConfirmationModal").modal("hide");
  }

  //save todo function
  function saveTodo(event, listBg = "") {
    let editableInput = $(event).parent("div").siblings("input");
    let content = editableInput.val().trim();
    let titleContent = content.length > 30 ? content : "";
    $(event).parents("li").attr("title", titleContent);
    const priority = listBg ? priorityCheck[listBg] : "";
    const priorirtyInfo = listBg
      ? `<br>with <span class=" text-sm">${priority}</span>`
      : "";

    // const messageWithColor=priority?`<span  class="text-${color}">${message}</span>`:message
    toastr.info(content, `Your task is succesfully edited ${priorirtyInfo}`, {
      positionClass: "toast-bottom-full-width",
    });
    let liSpanHTML = `   <span>
    ${content}
    </span>`;
    $(editableInput).replaceWith(liSpanHTML);
    const editBtnHtml = `  <button
    type="button"
    title="edit todo task"
    class="btn btn-xs btn-outline-primary btn-edit"
  >
    <i class="fa fa-pencil" aria-hidden="true"></i>
  </button>`;

    $(event).replaceWith(editBtnHtml);
  }

  $("#editTodoModal").on("shown.bs.modal", function () {
    $("#editTodoModal .li-edit-textarea").trigger("focus");
  });
  $("#deleteConfirmationModal").on("shown.bs.modal", function () {
    $("#deleteConfirmationModal .btn-light").trigger("focus");
  });
  function wrongMeassage() {
    toastr.warning("", "Something goes wrong <br>Please try again..", {
      positionClass: "toast-top-full-width",
      timeOut: 1000,
    });
  }
});
