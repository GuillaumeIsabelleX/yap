<?php include_once 'nav.php' ?>
    <div id="volunteers" class="container">
        <div class="row">
            <div class="col-sm">
                <div class="form-group">
                    <label for="new_volunteer_name"><?php echo $GLOBALS['add_add_new_volunteer']?></label>
                    <input type="text" name="new_volunteer_name" class="form-control" id="new_volunteer_name" aria-describedby="volunteerNameHelp" placeholder="<?php echo $GLOBALS['volunteer_name']?>">
                    <button id="add-volunteer" class="btn btn-lg btn-primary" type="button"><?php echo $GLOBALS['add_volunteer']?></button>
                    <button id="save-volunteers" class="btn btn-lg btn-primary" type="button"><?php echo $GLOBALS['save_volunteers']?></button>
                </div>
            </div>
        </div>
        <form id="volunteersForm">
            <select name="service_body_id" id="service_body_id">
                <option id="43">North Carolina Region</option>
            </select>
            <div id="volunteerCards" class="list-group-flush" class="row"></div>
        </form>
    </div>
<?php include_once 'footer.php';?>
<div class="card volunteerCard" id="volunteerCardTemplate" style="display:none;">
    <div class="card-body">
        <h5 class="card-title"><input type="text" id="volunteer_name" name="volunteer_name"></h5>
    </div>
</div>