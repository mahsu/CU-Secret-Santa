<div class="container">
    <div class="row">
        <h2> Frequently Asked Questions </h2>
        <br/>
        <dl>
            <dt> What is Cornell Secret Santa?</dt>
            <dd>In operation for its first year, <i>Cornell Secret Santa</i> is an app that makes it easy to organize secret santa gift exchanges throughout campus.
                Our goal is to foster community and giving in the Cornell community by organizing an intuitive
                <a href="http://www.wikihow.com/Do-a-Secret-Santa"> Secret Santa</a>
                through a web application. We'd love for you to participate in this year's festivities!
            </dd>

            <dt> How do I register?</dt>
            <dd>You can register <a href="<?php echo base_url('login') ?>">here</a>. Emails are restricted to the "cornell.edu"
                domain.
            </dd>
            <dt>What information do you collect?</dt>
            <dd>
                Your name and email address are the only information we store, so nothing that's not already <a target="_blank" href="https://www.cornell.edu/search/?tab=people">publicly available</a>. Authentication is done over Google's oauth api, and registration is restricted to only users who have a <i>cornell.edu</i> email. The project is <a href="https://github.com/mahsu/CU-Secret-Santa" target="_blank">open source</a> as well.
            </dd>
            <dt> What if I want to have a Secret Santa among my own friends?</dt>
            <dd><i>You can!</i> We implemented a unique "groups" feature that allows you to participate in multiple <i>
                    Secret Santas </i>.
                After you login/register, you are given to option to create your own group that has a specifically
                generated code. Once you share this code with your friends, you'll be able to be matched with your
                friends in that
                same group. It's as simple as that!
            </dd>

            <dt> What is the price range for gifts?</dt>
            <dd>For any public groups, try to keep your budgets around $10. Remember that creative and
                thoughtful gifts
                are more valuable than excessively pricey gifts!
            </dd>

            <dt> What kinds of gifts should I buy?</dt>
            <dd>You can buy anything that you think that your exchange partner would enjoy! Please make sure it's school
                appropriate,
                because you will be bringing your gifts into school.
            </dd>

            <dt>What do I do if I don't want to participate anymore?</dt>
            <dd>You can remove yourself from any group as long as you do so before <i> <?= date_format($partner_date,"m/d"); ?> (when partners are assigned). </i> Otherwise, don't back out of your commitments, or there will be repercussions. Don't be
                <i> that </i> guy.
            </dd>
            <dt>Is Cornell Secret Santa affiliated with Cornell University?</dt>
            <dd>Cornell Secret Santa is not in any way affiliated with Cornell University.</dd>
        </dl>
	<span style="text-align:center">
	<h3><i> Enjoy! </i></h3>
	</span>

        <h2> Special thanks... </h2>

        <ul>
            <li><b> Developers:</b> Matthew Hsu (Cornell University); Zachary Liu (Princeton University); Vincent Chen (Stanford University);</li>
        </ul>
    </div>

</div>