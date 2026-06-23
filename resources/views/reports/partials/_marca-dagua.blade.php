<div style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:0;">
    @for ($i = 0; $i < 9; $i++)
        <div style="position:absolute; top:{{ $i * 92 }}px; left:-60px; width:140%; text-align:center; transform:rotate(-30deg); white-space:nowrap;">
            @for ($j = 0; $j < 5; $j++)
                <span style="color:#eef1f5; font-size:34px; font-weight:bold; letter-spacing:.12em; margin:0 26px;">FISCALDOCK</span>
            @endfor
        </div>
    @endfor
</div>
