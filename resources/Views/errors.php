<?php
/**
 * @var string $errors
 * @var string $shortcode
 */
?>
<div id="error_container">
    <div>
        <h1>Woops Error:</h1>

        <?php if (isset($shortcode)){?>
            <h3>You have an error in shortcode</h3>
            <p>To use this shortcode you need to have the following syntax: </p>
            <code>
                <?= $shortcode ?>
            </code>
        <?php
        }
        ?>

        <h5>Details:</h5>
        <?php if (isset($errors)){?>
            <ul><?=$errors ?></ul>
        <?php
            }
        ?>
        <?php if (isset($details)){?>
            <p>"<?=$details ?>"</p>
        <?php
        }
        ?>
    </div>
</div>

<style>
    #error_container {
        width: 100%;
        display: flex;
        justify-content: center;

    }
    #error_container>div{
        border-color: #ff0000;
        background-color: #000000;
        border-width: 1px;
        border-radius: 10px;
        border-style: dashed;
        padding: 20px;
        width: 100%;
    }
    #error_container>div>h3{
        color: #ff0000
    }
    #error_container>div>h5{
        color: #ff0000
    }
    #error_container>div>h1{
        padding: unset!important;
        color: #ff0000
    }
    #error_container>div>p{
        color: #fff
    }
    code{
        color:#fff
    }
</style>