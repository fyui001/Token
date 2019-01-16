# トークンを生成してデータベースに挿入するやつ

 　一意性のあるトークンを作りデータベースに挿入できます
 
 ---
 
 
 
 
# 設定（例）


　・トークンに使用する文字や記号を入れる  
 `$token_char = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';`


・生成するトークンの数  
` $token_n = '1000'; `


・生成するトークンの文字数（長さ）  
` $token_len = '64'; `


・すでにDBにあるトークンをリセットするためのフラグ   
 trueならリセットして挿入, falseならDB内のトークンのコンフリクトを調査してトークンを追加する  
` $token_ResetFlg = true; `



