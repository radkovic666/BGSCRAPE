import os
import subprocess

# Define metadata for channels
channel_metadata = {
    "bnt": {"name": "БНТ 1", "tvg_id": "bnt1.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/bnt.png"},
    "bnt2": {"name": "БНТ 2", "tvg_id": "bnt2.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/bnt2.png"},
    "bnthd": {"name": "БНТ 3", "tvg_id": "bnt3.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/bnthd-1.png"},
    "bnt4": {"name": "БНТ 4", "tvg_id": "bnt4.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/bnt4.png"},
    "btv": {"name": "bTV", "tvg_id": "btv.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/btv-9.png"},
    "nova": {"name": "Nova TV", "tvg_id": "nova.bg", "group_title": "Национални", "logo": "https://hajanddebono.com/images/nova-1.png"},

    "btvcinema": {"name": "bTV Cinema", "tvg_id": "btvcinema.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/btvcinema.png"},
    "btvaction": {"name": "bTV Action", "tvg_id": "btvaction.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/btvaction.png"},
    "btvcomedy": {"name": "bTV Comedy", "tvg_id": "btvcomedy.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/btvcomedy.png"},
    "btvlady": {"name": "bTV Story", "tvg_id": "btvlady.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/btvlady.png"},
    "diema": {"name": "Diema", "tvg_id": "diema.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/diema.png"},
    "diemaf": {"name": "Diema Family", "tvg_id": "diemafamily.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/diemaf.png"},
    "kino": {"name": "Kino Nova", "tvg_id": "kinonova.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/kinonova.png"},
    "fox": {"name": "Star Channel", "tvg_id": "fox.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/fox-1.png"},
    "foxcrime": {"name": "Star Crime", "tvg_id": "foxcrime.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/foxcrime-1.png"},
    "foxlife": {"name": "Star Life", "tvg_id": "foxlife.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/foxlife.png"},
    "epicdrama": {"name": "Epic Drama", "tvg_id": "epicdrama.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/epic_drama.png"},
    "amc": {"name": "AMC", "tvg_id": "amc.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/amc-1.png"},
    "axn": {"name": "AXN", "tvg_id": "axn.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/axn.png"},
    "moviestar": {"name": "MovieStar", "tvg_id": "moviestar.bg", "group_title": "Филми", "logo": "https://hajanddebono.com/images/moviestar-7-46.png"},
    "filmbox": {"name": "HBO", "tvg_id": "hbo.bg", "group_title": "Филми", "logo": "https://ia804502.us.archive.org/33/items/logo.brootv_hbo/HBO.png"},
    "filmboxextra": {"name": "FilmBox Extra", "tvg_id": "filmboxextra.com", "group_title": "Филми", "logo": "https://hajanddebono.com/images/filmboxextra.png"},
    "filmboxplus": {"name": "FilmBox Stars", "tvg_id": "filmboxstars.com", "group_title": "Филми", "logo": "https://hajanddebono.com/images/filmboxplus.png"},

    "diemasport": {"name": "Diema Sport", "tvg_id": "diemasport.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/diemasport.png"},
    "diemasport2": {"name": "Diema Sport 2", "tvg_id": "diemasport2.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/diemasport2.png"},
    "diemasport3": {"name": "Diema Sport 3", "tvg_id": "diemasport3.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/diemasport3.png"},
    "novasport": {"name": "Nova Sport", "tvg_id": "novasport.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/nova_sport.png"},
    "eurosport": {"name": "Eurosport 1", "tvg_id": "eurosport1.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/eurosport.png"},
    "eurosport2": {"name": "Eurosport 2", "tvg_id": "eurosport2.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/eurosport2-1.png"},
    "mtelsport": {"name": "MAX Sport 1", "tvg_id": "maxsport1.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/maxsport1.png"},
    "mtelsport2": {"name": "MAX Sport 2", "tvg_id": "maxsport2.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/maxsport2.png"},
    "mtelsport3": {"name": "MAX Sport 3", "tvg_id": "maxsport3.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/maxsport3.png"},
    "mtelsport4": {"name": "MAX Sport 4", "tvg_id": "maxsport4.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/maxsport4.png"},
    "ringbg": {"name": "RING BG", "tvg_id": "ring.bg", "group_title": "Спорт", "logo": "https://hajanddebono.com/images/ringbg.png"},

    "historytv": {"name": "History Channel", "tvg_id": "historytv.com", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/history-1.png"},
    "discovery": {"name": "Discovery Channel", "tvg_id": "discoverychannel.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/discovery.png"},
    "idx": {"name": "Investigation Discovery", "tvg_id": "bnt4.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/idx-6-14.png"},
    "ng": {"name": "National Geographic", "tvg_id": "natgeo.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/ng.png"},
    "natgeowild": {"name": "Nat Geo Wild", "tvg_id": "natgeowild.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/natgeowild.png"},
    "animalplanet": {"name": "Animal Planet", "tvg_id": "animalplanet.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/animalplanet.png"},
    "docubox": {"name": "Docubox", "tvg_id": "docubox.spiintl.com", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/docubox.png"},
    "tlc": {"name": "TLC", "tvg_id": "tlc.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/tlc-1.png"},
    "24kitchen": {"name": "24Kitchen", "tvg_id": "24kitchen.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/24kitchen-2.png"},
    "viasat_history": {"name": "Viasat History", "tvg_id": "history.viasat.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/viasat_history.png"},
    "viasat_nature": {"name": "Viasat Nature", "tvg_id": "nature.viasat.bg", "group_title": "Научнопопулярни", "logo": "https://hajanddebono.com/images/viasat_nature.png"},

    "cartoon": {"name": "Cartoon Network", "tvg_id": "cartoonnetwork.tv", "group_title": "Детски", "logo": "https://hajanddebono.com/images/cartoon.png"},
    "disney": {"name": "Disney Channel", "tvg_id": "disneychannel.bg", "group_title": "Детски", "logo": "https://hajanddebono.com/images/disney-2.png"},
    "nickelodeon": {"name": "Nickelodeon", "tvg_id": "nickelodeon.tv", "group_title": "Детски", "logo": "https://hajanddebono.com/images/nickelodeon-1.png"},
    "nicktoons": {"name": "NickToons", "tvg_id": "nicktoons.tv", "group_title": "Детски", "logo": "https://hajanddebono.com/images/nicktoon-png-8.png"},
    "nickjr": {"name": "Nick Jr", "tvg_id": "nick.tv", "group_title": "Детски", "logo": "https://hajanddebono.com/images/nickjr_png.png"},

    
    "planetahd": {"name": "Planeta HD", "tvg_id": "planetatv.bg", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/planetahd.png"},
    "planetafolk": {"name": "Planeta Folk", "tvg_id": "planetafolk.bg", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/planetafolk.png"},
    "fantv": {"name": "FEN TV", "tvg_id": "fentv.bg", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/fantv.png"},
    "balkanika": {"name": "Balkanika", "tvg_id": "balkanika.bg", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/balkanika.png"},
    "thevoice": {"name": "The Voice", "tvg_id": "thevoice.bg", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/thevoice.png"},
    "magictv": {"name": "Magic TV", "group_title": "Музикални", "logo": "https://hajanddebono.com/images/MagicTV_logo.png"},

    "hls": {"name": "Evrokom", "tvg_id": "evrokom.bg", "group_title": "Други", "logo": "https://hajanddebono.com/images/eurocom.png"},
    "travel": {"name": "Travel TV", "tvg_id": "traveltv.bg", "group_title": "Други", "logo": "https://hajanddebono.com/images/travel-1.png"},
    "stream04": {"name": "Deutsche Welle", "tvg_id": "deutschewelle.de", "group_title": "Други", "logo": "https://hajanddebono.com/images/dwelle.png"}
}



# File paths
input_file = "temp.txt"
output_file = "playlist.m3u"

# M3U Header
m3u_header = "#EXTM3U\n"

def generate_m3u():
    if not os.path.exists(input_file):
        print(f"Error: {input_file} does not exist.")
        return
    
    with open(input_file, "r") as infile:
        lines = infile.readlines()

    # Start building M3U content
    m3u_content = [m3u_header]
    
    for line in lines:
        url = line.strip()
        if not url:
            continue
        
        # Extract channel identifier from URL
        channel_id = url.split('/')[-2]
        metadata = channel_metadata.get(channel_id, {})
        
        # Fallbacks for missing metadata
        name = metadata.get("name", channel_id.capitalize())
        tvg_id = metadata.get("tvg_id", "")
        group_title = metadata.get("group_title", "Unknown")
        logo = metadata.get("logo", "")
        
        # Add channel entry to M3U content
        m3u_content.append(
            f'#EXTINF:-1 tvg-id="{tvg_id}" tvg-logo="{logo}" group-title="{group_title}",{name}\n{url}\n'
        )
    
    # Write to output file
    with open(output_file, "w", encoding='utf-8') as outfile:
        outfile.writelines(m3u_content)
    #print(f"{output_file} has been successfully created!")

def call_ftp_script():
    # Define the path to the ftp.py script
    script_path = os.path.join(os.getcwd(), 'ftp.py')
    
    # Run the ftp.py script using subprocess
    subprocess.run(['python', script_path], check=True)

# Run the function
generate_m3u()
call_ftp_script()
